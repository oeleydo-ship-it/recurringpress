<?php

namespace Tests;

use App\Http\Controllers\SettingsController;
use App\Models\CompanySetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Console\Kernel;
use PHPUnit\Framework\TestCase;

class InvoiceTemplatesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $app = require __DIR__.'/../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();
        restore_error_handler();
        restore_exception_handler();
    }

    public function test_five_new_designs_render_as_single_page_pdfs_with_consistent_sample_totals(): void
    {
        foreach (['uae', 'philippines', 'international', 'executive', 'commerce'] as $template) {
            self::assertArrayHasKey($template, CompanySetting::invoiceTemplates());
            $view = (new SettingsController)->previewTemplate($template);
            $html = $view->render();
            self::assertStringNotContainsString('gradient(', $html);
            $invoice = $view->getData()['invoice'];
            $currency = $invoice->company->currency;
            self::assertStringContainsString($currency.' '.number_format($invoice->total, 2), $html);
            self::assertEqualsWithDelta($invoice->total, $invoice->items->sum('total'), 0.01);
            if ($template === 'uae') {
                self::assertStringContainsString('TRN:', $html);
                self::assertSame('AED', $currency);
            }
            if ($template === 'philippines') {
                self::assertStringContainsString('TIN:', $html);
                self::assertSame('PHP', $currency);
            }
            $pdf = Pdf::loadHTML($html)->setPaper('a4');
            self::assertStringStartsWith('%PDF-', $pdf->output());
            self::assertSame(1, $pdf->getDomPDF()->getCanvas()->get_page_count(), $template.' should fit the sample on one page');
        }
    }

    public function test_many_items_paginate_and_partial_payment_is_shown(): void
    {
        $view = (new SettingsController)->previewTemplate('commerce');
        $invoice = $view->getData()['invoice'];
        $item = $invoice->items->first();
        $invoice->setRelation('items', collect(range(1, 55))->map(function ($index) use ($item) {
            $copy = clone $item;
            $copy->name = 'Service line '.$index;
            return $copy;
        }));
        $invoice->calculateTotals();
        $invoice->paid_amount = 200;
        $html = $view->render();
        self::assertStringContainsString('Amount paid', $html);
        self::assertStringContainsString('Service line 55', $html);
        $pdf = Pdf::loadHTML($html)->setPaper('a4');
        $pdf->output();
        self::assertGreaterThan(1, $pdf->getDomPDF()->getCanvas()->get_page_count());
    }
}
