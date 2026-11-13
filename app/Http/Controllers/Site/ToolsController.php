<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Support\QrCode;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Free tools of the public website. Each one relies on rules that do not change
 * (simple ratios, QR codes, checklists): nothing to update over time.
 */
class ToolsController extends Controller
{
    public const TOOLS = [
        'salaire-pour-louer' => ['Calculateur : quel salaire pour louer ?', 'Le revenu conseillé pour un loyer donné, et le loyer maximum pour vos revenus, selon la règle des 3 fois le loyer.'],
        'generateur-qr-code' => ['Générateur de QR code pour annonce immobilière', 'Créez gratuitement un QR code vers votre annonce ou votre page de visite, à imprimer sur une vitrine, un panneau ou un flyer.'],
        'checklist-visite-appartement' => ['Checklist de visite d\'appartement à imprimer', 'Les points à vérifier pièce par pièce et les questions à poser pendant une visite, à imprimer ou à garder sur votre téléphone.'],
        'modele-bon-de-visite' => ['Modèle de bon de visite gratuit à imprimer', 'Un modèle de bon de visite prêt à imprimer pour les visites immobilières, à faire signer par le visiteur.'],
    ];

    public function index(): View
    {
        return view('site.tools.index', ['tools' => self::TOOLS]);
    }

    public function rentSalary(Request $request): View
    {
        $rent = $this->amount($request->query('loyer'));
        $income = $this->amount($request->query('revenus'));

        return view('site.tools.rent-salary', [
            'rent' => $rent,
            'income' => $income,
            'requiredIncome' => $rent ? $rent * 3 : null,
            'maxRent' => $income ? floor($income / 3) : null,
        ]);
    }

    public function qrCode(Request $request): View
    {
        $url = $this->validUrl($request->query('url'));

        return view('site.tools.qr-code', [
            'url' => $url,
            'invalid' => filled($request->query('url')) && $url === null,
            'qr' => $url ? QrCode::svg($url, 480) : null,
        ]);
    }

    public function qrCodeDownload(Request $request): Response
    {
        $url = $this->validUrl($request->query('url'));
        abort_if($url === null, 422);

        return response(QrCode::svg($url, 1000), 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="qr-code.svg"',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    public function checklist(): View
    {
        return view('site.tools.checklist');
    }

    public function voucherTemplate(): View
    {
        return view('site.tools.voucher-template');
    }

    private function amount(mixed $value): ?float
    {
        $value = is_string($value) ? (float) str_replace([' ', ','], ['', '.'], $value) : 0;

        return $value > 0 && $value < 1_000_000 ? round($value, 2) : null;
    }

    private function validUrl(mixed $value): ?string
    {
        if (! is_string($value) || strlen($value) > 500) {
            return null;
        }

        $value = trim($value);
        if ($value !== '' && ! preg_match('#^https?://#i', $value)) {
            $value = 'https://'.$value;
        }

        return filter_var($value, FILTER_VALIDATE_URL) && preg_match('#^https?://[^/\s]+\.[^/\s]+#i', $value) ? $value : null;
    }
}
