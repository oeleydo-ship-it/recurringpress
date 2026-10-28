<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanySetting extends Model
{
    use HasFactory;

    /**
     * Never serialize gateway secrets. Inertia sends props to the browser as JSON, so
     * anything not hidden here would be readable in page source (including public pages).
     */
    protected $hidden = [
        'stripe_secret_key',
        'stripe_webhook_secret',
        'smtp_password',
    ];

    protected $fillable = [
        'company_id',
        'date_format',
        'timezone',
        'language',
        'tax_enabled',
        'default_tax_rate',
        'email_notifications',
        'invoice_due_reminder',
        'overdue_reminder',
        'theme_color',
        'primary_color',
        'secondary_color',
        'sidebar_color',
        'sidebar_text_color',
        'show_logo_on_invoice',
        'invoice_template',
        'show_payment_instructions',
        'payment_instructions',
        'bank_name',
        'bank_account_name',
        'bank_account_number',
        'bank_routing_number',
        'paypal_email',
        'stripe_public_key',
        'stripe_secret_key',
        'stripe_webhook_secret',
        'stripe_enabled',
        'payment_gateway_mode',
        'accept_bank_transfer',
        'accept_cash',
        'accept_stripe',
        'font_family',
        'font_size',
        'heading_color',
        'heading_color',
        'text_color',
        'default_notes',
        'default_terms',
        'show_authorized_signature',
        'show_invoice_footer',
        'show_quotation_website',
        'show_quotation_message',
        'quotation_message',
        'smtp_enabled',
        'smtp_host',
        'smtp_port',
        'smtp_encryption',
        'smtp_username',
        'smtp_password',
        'smtp_from_address',
        'smtp_from_name',
    ];

    protected $casts = [
        'tax_enabled' => 'boolean',
        'default_tax_rate' => 'decimal:2',
        'email_notifications' => 'boolean',
        'invoice_due_reminder' => 'boolean',
        'overdue_reminder' => 'boolean',
        'show_logo_on_invoice' => 'boolean',
        'show_payment_instructions' => 'boolean',
        'stripe_enabled' => 'boolean',
        'accept_bank_transfer' => 'boolean',
        'accept_cash' => 'boolean',
        'accept_stripe' => 'boolean',
        'show_authorized_signature' => 'boolean',
        'show_invoice_footer' => 'boolean',
        'show_quotation_website' => 'boolean',
        'show_quotation_message' => 'boolean',
        'cf_dns_records' => 'array',
        'custom_domain_verified_at' => 'datetime',
        'custom_domain_checked_at' => 'datetime',
        'smtp_enabled' => 'boolean',
        'smtp_port' => 'integer',
        'smtp_password' => 'encrypted',
        'smtp_verified_at' => 'datetime',
        'smtp_last_error_at' => 'datetime',
    ];

    /** A custom domain is only used for links once its DNS has been verified. */
    public function hasVerifiedCustomDomain(): bool
    {
        return \App\Support\TenantDomain::enabled() && $this->company?->canUseFeature('custom_domain') && filled($this->custom_domain) && $this->custom_domain_verified_at !== null;
    }

    public function hasActiveSubdomain(): bool
    {
        return \App\Support\TenantDomain::subdomainsEnabled() && $this->company?->canUseFeature('subdomain') && filled($this->subdomain);
    }

    /** Client emails go through the tenant's SMTP only when it is switched on and a test send succeeded. */
    public function usesOwnSmtp(): bool
    {
        return $this->smtp_enabled && filled($this->smtp_host) && filled($this->smtp_from_address) && $this->smtp_verified_at !== null;
    }

    public static function invoiceTemplates(): array
    {
        return [
            'classic' => ['name' => 'Classic', 'description' => 'Clean and professional', 'preview' => 'classic'],
            'modern' => ['name' => 'Modern', 'description' => 'Bold with accent colors', 'preview' => 'modern'],
            'minimal' => ['name' => 'Minimal', 'description' => 'Simple and elegant', 'preview' => 'minimal'],
            'corporate' => ['name' => 'Corporate', 'description' => 'Formal business style', 'preview' => 'corporate'],
            'elegant' => ['name' => 'Elegant', 'description' => 'Sophisticated design', 'preview' => 'elegant'],
            'creative' => ['name' => 'Creative', 'description' => 'Unique and colorful', 'preview' => 'creative'],
            'professional' => ['name' => 'Professional', 'description' => 'Executive style', 'preview' => 'professional'],
            'simple' => ['name' => 'Simple', 'description' => 'Basic and clean', 'preview' => 'simple'],
            'bold' => ['name' => 'Bold', 'description' => 'High contrast & strong', 'preview' => 'bold'],
            'striped' => ['name' => 'Striped', 'description' => 'Alternating rows theme', 'preview' => 'striped'],
            'tech' => ['name' => 'Tech', 'description' => 'Modern geometric style', 'preview' => 'tech'],
            'luxe' => ['name' => 'Luxe', 'description' => 'Premium & elegant', 'preview' => 'luxe'],
        ];
    }

    public static function fontFamilies(): array
    {
        return [
            'Inter' => 'Inter',
            'Poppins' => 'Poppins',
            'Roboto' => 'Roboto',
            'Open Sans' => 'Open Sans',
            'Lato' => 'Lato',
            'Nunito' => 'Nunito',
        ];
    }

    public static function fontSizes(): array
    {
        return [
            'small' => 'Small (14px)',
            'base' => 'Normal (16px)',
            'large' => 'Large (18px)',
        ];
    }

    public static function colorPresets(): array
    {
        return [
            'indigo' => ['primary' => '#4f46e5', 'secondary' => '#6366f1', 'sidebar' => '#1e1b4b', 'sidebar_text' => '#ffffff'],
            'blue' => ['primary' => '#2563eb', 'secondary' => '#3b82f6', 'sidebar' => '#1e3a5f', 'sidebar_text' => '#ffffff'],
            'emerald' => ['primary' => '#059669', 'secondary' => '#10b981', 'sidebar' => '#064e3b', 'sidebar_text' => '#ffffff'],
            'rose' => ['primary' => '#e11d48', 'secondary' => '#f43f5e', 'sidebar' => '#4c0519', 'sidebar_text' => '#ffffff'],
            'amber' => ['primary' => '#d97706', 'secondary' => '#f59e0b', 'sidebar' => '#451a03', 'sidebar_text' => '#ffffff'],
            'violet' => ['primary' => '#7c3aed', 'secondary' => '#8b5cf6', 'sidebar' => '#2e1065', 'sidebar_text' => '#ffffff'],
            'slate' => ['primary' => '#475569', 'secondary' => '#64748b', 'sidebar' => '#1e293b', 'sidebar_text' => '#ffffff'],
            'teal' => ['primary' => '#0d9488', 'secondary' => '#14b8a6', 'sidebar' => '#134e4a', 'sidebar_text' => '#ffffff'],
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public static function dateFormats(): array
    {
        return [
            'Y-m-d' => 'YYYY-MM-DD (2024-01-15)',
            'd/m/Y' => 'DD/MM/YYYY (15/01/2024)',
            'm/d/Y' => 'MM/DD/YYYY (01/15/2024)',
            'd-m-Y' => 'DD-MM-YYYY (15-01-2024)',
            'd.m.Y' => 'DD.MM.YYYY (15.01.2024)',
            'M d, Y' => 'Mon DD, YYYY (Jan 15, 2024)',
            'F d, Y' => 'Month DD, YYYY (January 15, 2024)',
        ];
    }

    public static function currencies(): array
    {
        return [
            // Major Currencies
            'USD' => 'US Dollar ($)',
            'EUR' => 'Euro (€)',
            'GBP' => 'British Pound (£)',
            'JPY' => 'Japanese Yen (¥)',
            'CHF' => 'Swiss Franc (CHF)',
            // North America
            'CAD' => 'Canadian Dollar (C$)',
            'MXN' => 'Mexican Peso (MX$)',
            // South America
            'BRL' => 'Brazilian Real (R$)',
            'ARS' => 'Argentine Peso (ARS)',
            'CLP' => 'Chilean Peso (CLP)',
            'COP' => 'Colombian Peso (COP)',
            'PEN' => 'Peruvian Sol (S/)',
            // Europe
            'SEK' => 'Swedish Krona (kr)',
            'NOK' => 'Norwegian Krone (kr)',
            'DKK' => 'Danish Krone (kr)',
            'PLN' => 'Polish Zloty (zł)',
            'CZK' => 'Czech Koruna (Kč)',
            'HUF' => 'Hungarian Forint (Ft)',
            'RON' => 'Romanian Leu (lei)',
            'BGN' => 'Bulgarian Lev (лв)',
            'HRK' => 'Croatian Kuna (kn)',
            'RUB' => 'Russian Ruble (₽)',
            'UAH' => 'Ukrainian Hryvnia (₴)',
            'TRY' => 'Turkish Lira (₺)',
            // Asia Pacific
            'CNY' => 'Chinese Yuan (¥)',
            'HKD' => 'Hong Kong Dollar (HK$)',
            'TWD' => 'Taiwan Dollar (NT$)',
            'KRW' => 'South Korean Won (₩)',
            'SGD' => 'Singapore Dollar (S$)',
            'MYR' => 'Malaysian Ringgit (RM)',
            'THB' => 'Thai Baht (฿)',
            'IDR' => 'Indonesian Rupiah (Rp)',
            'PHP' => 'Philippine Peso (₱)',
            'VND' => 'Vietnamese Dong (₫)',
            'INR' => 'Indian Rupee (₹)',
            'PKR' => 'Pakistani Rupee (Rs)',
            'BDT' => 'Bangladeshi Taka (৳)',
            'LKR' => 'Sri Lankan Rupee (Rs)',
            'NPR' => 'Nepalese Rupee (Rs)',
            // Oceania
            'AUD' => 'Australian Dollar (A$)',
            'NZD' => 'New Zealand Dollar (NZ$)',
            'FJD' => 'Fiji Dollar (FJ$)',
            // Middle East
            'AED' => 'UAE Dirham (AED)',
            'SAR' => 'Saudi Riyal (﷼)',
            'QAR' => 'Qatari Riyal (﷼)',
            'KWD' => 'Kuwaiti Dinar (د.ك)',
            'BHD' => 'Bahraini Dinar (BD)',
            'OMR' => 'Omani Rial (﷼)',
            'JOD' => 'Jordanian Dinar (JD)',
            'ILS' => 'Israeli Shekel (₪)',
            'EGP' => 'Egyptian Pound (E£)',
            // Africa
            'ZAR' => 'South African Rand (R)',
            'NGN' => 'Nigerian Naira (₦)',
            'KES' => 'Kenyan Shilling (KSh)',
            'GHS' => 'Ghanaian Cedi (₵)',
            'MAD' => 'Moroccan Dirham (د.م.)',
            'TND' => 'Tunisian Dinar (DT)',
            'UGX' => 'Ugandan Shilling (USh)',
            'TZS' => 'Tanzanian Shilling (TSh)',
            'ETB' => 'Ethiopian Birr (Br)',
            'XOF' => 'West African CFA Franc (CFA)',
            'XAF' => 'Central African CFA Franc (FCFA)',
        ];
    }

    public static function getCurrencySymbol(string $code): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'CHF' => 'CHF',
            'CAD' => 'C$',
            'MXN' => 'MX$',
            'BRL' => 'R$',
            'ARS' => 'ARS',
            'CLP' => 'CLP',
            'COP' => 'COP',
            'PEN' => 'S/',
            'SEK' => 'kr',
            'NOK' => 'kr',
            'DKK' => 'kr',
            'PLN' => 'zł',
            'CZK' => 'Kč',
            'HUF' => 'Ft',
            'RON' => 'lei',
            'BGN' => 'лв',
            'HRK' => 'kn',
            'RUB' => '₽',
            'UAH' => '₴',
            'TRY' => '₺',
            'CNY' => '¥',
            'HKD' => 'HK$',
            'TWD' => 'NT$',
            'KRW' => '₩',
            'SGD' => 'S$',
            'MYR' => 'RM',
            'THB' => '฿',
            'IDR' => 'Rp',
            'PHP' => '₱',
            'VND' => '₫',
            'INR' => '₹',
            'PKR' => 'Rs',
            'BDT' => '৳',
            'LKR' => 'Rs',
            'NPR' => 'Rs',
            'AUD' => 'A$',
            'NZD' => 'NZ$',
            'FJD' => 'FJ$',
            'AED' => 'AED',
            'SAR' => '﷼',
            'QAR' => '﷼',
            'KWD' => 'د.ك',
            'BHD' => 'BD',
            'OMR' => '﷼',
            'JOD' => 'JD',
            'ILS' => '₪',
            'EGP' => 'E£',
            'ZAR' => 'R',
            'NGN' => '₦',
            'KES' => 'KSh',
            'GHS' => '₵',
            'MAD' => 'د.م.',
            'TND' => 'DT',
            'UGX' => 'USh',
            'TZS' => 'TSh',
            'ETB' => 'Br',
            'XOF' => 'CFA',
            'XAF' => 'FCFA',
        ];
        return $symbols[$code] ?? $code;
    }
}
