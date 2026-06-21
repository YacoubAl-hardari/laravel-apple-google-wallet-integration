<?php

namespace Yacoubalhaidari\AppleGoogleWallet\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Yacoubalhaidari\AppleGoogleWallet\Apple\AppleWalletService;
use Yacoubalhaidari\AppleGoogleWallet\Apple\PkPassGenerator;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\LoyaltyProgramData;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\MemberCardData;
use Yacoubalhaidari\AppleGoogleWallet\Studio\WalletConfigExporter;
use Yacoubalhaidari\AppleGoogleWallet\Studio\WalletStudioConfigApplier;
use Yacoubalhaidari\AppleGoogleWallet\Studio\WalletStudioDefaults;
use Yacoubalhaidari\AppleGoogleWallet\Studio\WalletStudioPreviewService;
use ZipArchive;

class WalletStudioController extends Controller
{
    public function index()
    {
        return view('apple-google-wallet::studio.index', [
            'defaults' => WalletStudioDefaults::form(),
            'langKeys' => WalletStudioDefaults::langKeys(),
            'appleFieldSlots' => WalletStudioDefaults::appleFieldSlots(),
            'googleFieldSlots' => WalletStudioDefaults::googleFieldSlots(),
        ]);
    }

    public function export(Request $request, WalletConfigExporter $exporter): JsonResponse
    {
        return response()->json([
            'files' => $exporter->export($this->validateStudio($request)),
        ]);
    }

    public function downloadZip(Request $request, WalletConfigExporter $exporter): BinaryFileResponse
    {
        $files = $exporter->export($this->validateStudio($request));
        $tmp = tempnam(sys_get_temp_dir(), 'wallet_studio_');
        $zipPath = $tmp . '.zip';
        @unlink($tmp);

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }

        $zip->close();

        return response()->download($zipPath, 'wallet-design.zip')->deleteFileAfterSend(true);
    }

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|max:4096',
        ]);

        $path = $request->file('image')->store('wallet-studio', 'public');

        return response()->json([
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
        ]);
    }

    public function preview(WalletStudioPreviewService $preview, Request $request): JsonResponse
    {
        return response()->json(
            $preview->generate($this->validateStudio($request))
        );
    }

    public function testPass(
        Request $request,
        WalletStudioConfigApplier $applier,
        AppleWalletService $appleWallet,
    ): BinaryFileResponse|JsonResponse {
        $input = $this->validateStudio($request);
        $applier->apply($input);

        if (! $appleWallet->isConfigured()) {
            return response()->json([
                'message' => 'Apple Wallet is not configured. Add certificates in .env first.',
            ], 422);
        }

        $program = new LoyaltyProgramData(
            id: 'studio',
            name: (string) ($input['preview_program'] ?? 'Preview'),
            requiredStamps: max(1, (int) ($input['preview_stamps_total'] ?? 10)),
            rewardCount: 1,
            imageUrl: $input['logo_url'] ?? null,
        );

        $member = new MemberCardData(
            id: 'studio',
            qrCode: 'STUDIO-PREVIEW-QR',
            stampsProgress: max(0, (int) ($input['preview_stamps_filled'] ?? 3)),
            rewardsEarned: max(0, (int) ($input['preview_rewards'] ?? 0)),
            isCompleted: (int) ($input['preview_stamps_filled'] ?? 0) >= (int) ($input['preview_stamps_total'] ?? 10),
            memberName: (string) ($input['preview_member'] ?? 'Preview'),
        );

        $pkpass = $appleWallet->createPass($program, $member);

        if (! $pkpass) {
            return response()->json([
                'message' => $appleWallet->getLastError() ?? 'Failed to generate pass.',
            ], 422);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'wallet_studio_pass_');
        file_put_contents($tmp, $pkpass);

        return response()->download($tmp, 'studio-preview.pkpass', [
            'Content-Type' => PkPassGenerator::getPassMimeType(),
        ])->deleteFileAfterSend(true);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateStudio(Request $request): array
    {
        return $request->validate([
            'platform' => 'required|in:apple,google,both',
            'preview_locale' => 'required|in:ar,en',
            'preview_stamps_filled' => 'required|integer|min:0|max:50',
            'preview_stamps_total' => 'required|integer|min:1|max:50',
            'preview_rewards' => 'required|integer|min:0|max:99',
            'preview_member' => 'required|string|max:40',
            'preview_program' => 'required|string|max:60',
            'stamp_columns' => 'required|integer|min:1|max:10',
            'logo_path' => 'nullable|string|max:255',
            'logo_url' => 'nullable|string|max:500',
            'strip_bg_path' => 'nullable|string|max:255',
            'strip_bg_url' => 'nullable|string|max:500',
            'apple_background' => 'nullable|string|max:20',
            'apple_foreground' => 'nullable|string|max:20',
            'apple_label' => 'nullable|string|max:20',
            'apple_stamp_completed' => 'nullable|string|max:20',
            'apple_stamp_empty_fill' => 'nullable|string|max:20',
            'apple_stamp_empty_border' => 'nullable|string|max:20',
            'apple_strip_overlay' => 'nullable|numeric|min:0|max:1',
            'google_background' => 'nullable|string|max:20',
            'google_stamp_filled' => 'nullable|string|max:20',
            'google_stamp_empty' => 'nullable|string|max:20',
            'google_stamp_border' => 'nullable|string|max:20',
            'google_strip_bg' => 'nullable|string|max:20',
            'google_strip_overlay' => 'nullable|numeric|min:0|max:1',
            'google_stamp_text' => 'nullable|string|max:20',
            'apple_secondary_order' => 'nullable|array',
            'apple_auxiliary_order' => 'nullable|array',
            'apple_visible_fields' => 'nullable|array',
            'google_modules_order' => 'nullable|array',
            'google_visible_fields' => 'nullable|array',
            'lang_ar' => 'nullable|array',
            'lang_en' => 'nullable|array',
        ]);
    }
}
