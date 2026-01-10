<?php

namespace App\Services;

use App\Contracts\WhatsappInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FontteService implements WhatsappInterface
{
    protected ?string $token;
    protected string $baseUrl = 'https://api.fonnte.com';

    public function __construct()
    {
        $this->token = config('services.fontte.token', env('FONTTE_TOKEN', ''));
    }

    /**
     * Send WhatsApp message via Fontte API
     *
     * @param string $noTujuan Target phone number
     * @param string $message Message content
     * @return array Response from Fontte API
     */
    public function send(string $noTujuan, string $message): array
    {
        if (empty($this->token)) {
            Log::error('Fontte: Token tidak ditemukan');
            return [
                'success' => false,
                'message' => 'Token tidak ditemukan',
            ];
        }

        // Normalize phone number - remove leading 0 and add 62
        $noTujuan = $this->normalizePhoneNumber($noTujuan);

        if (empty($noTujuan)) {
            Log::warning('Fontte: Nomor tujuan kosong');
            return [
                'success' => false,
                'message' => 'Nomor tujuan kosong',
            ];
        }

        try {
            // Build HTTP request
            $http = Http::withHeaders([
                'Authorization' => $this->token,
            ]);
            
            // Disable SSL verification for local development (macOS SSL cert issue)
            if (app()->isLocal()) {
                $http = $http->withoutVerifying();
            }
            
            $response = $http->asForm()->post($this->baseUrl . '/send', [
                'target' => $noTujuan,
                'message' => $message,
                'countryCode' => '62',
            ]);

            $result = $response->json();

            if ($response->successful() && ($result['status'] ?? false)) {
                Log::info('Fontte: Pesan berhasil dikirim', [
                    'target' => $noTujuan,
                    'response' => $result,
                ]);

                return [
                    'success' => true,
                    'message' => 'Pesan berhasil dikirim',
                    'data' => $result,
                ];
            }

            Log::warning('Fontte: Gagal mengirim pesan', [
                'target' => $noTujuan,
                'response' => $result,
            ]);

            return [
                'success' => false,
                'message' => $result['reason'] ?? $result['message'] ?? 'Gagal mengirim pesan',
                'data' => $result,
            ];
        } catch (\Exception $e) {
            Log::error('Fontte: Exception saat mengirim pesan', [
                'target' => $noTujuan,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Normalize phone number to Indonesian format
     * Remove leading 0 and ensure it starts with 62
     *
     * @param string $phoneNumber
     * @return string
     */
    protected function normalizePhoneNumber(string $phoneNumber): string
    {
        // Remove any non-numeric characters
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

        if (empty($phoneNumber)) {
            return '';
        }

        // If starts with 0, remove it
        if (str_starts_with($phoneNumber, '0')) {
            $phoneNumber = substr($phoneNumber, 1);
        }

        // If starts with 62, keep it
        if (str_starts_with($phoneNumber, '62')) {
            return $phoneNumber;
        }

        // Add 62 prefix
        return '62' . $phoneNumber;
    }
}
