import { usePage } from '@inertiajs/react';
import { Download } from 'lucide-react';
import { StatusBadge, Table, TBody, TD, TH, THead, TR } from '@/Components/ui';
import { formatDate, formatMoney } from '@/lib/utils';

function PdfLink({ href, number }) {
    return (
        <a href={href} aria-label={`Download ${number} as PDF`} className="inline-flex size-8 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-800">
            <Download className="size-4" />
        </a>
    );
}

export function InvoiceTable({ invoices }) {
    const { portal } = usePage().props;
    const money = (v) => formatMoney(v, portal.currency);

    return (
        <Table>
            <THead>
                <TH>Invoice</TH>
                <TH className="hidden sm:table-cell">Issued</TH>
                <TH className="hidden sm:table-cell">Due</TH>
                <TH>Status</TH>
                <TH align="right">Total</TH>
                <TH align="right" className="hidden sm:table-cell">Amount due</TH>
                <TH align="right"><span className="sr-only">PDF</span></TH>
            </THead>
            <TBody>
                {invoices.map((i) => (
                    <TR key={i.id} href={i.url}>
                        <TD className="font-medium text-slate-900">{i.number}</TD>
                        <TD className="hidden sm:table-cell">{formatDate(i.issue_date, portal.date_format)}</TD>
                        <TD className="hidden sm:table-cell">{formatDate(i.due_date, portal.date_format)}</TD>
                        <TD><StatusBadge status={i.status} /></TD>
                        <TD align="right" className="font-medium text-slate-900">{money(i.total)}</TD>
                        <TD align="right" className="hidden sm:table-cell">{money(i.amount_due)}</TD>
                        <TD align="right"><PdfLink href={i.pdf_url} number={i.number} /></TD>
                    </TR>
                ))}
            </TBody>
        </Table>
    );
}

export function QuotationTable({ quotations }) {
    const { portal } = usePage().props;

    return (
        <Table>
            <THead>
                <TH>Quotation</TH>
                <TH className="hidden sm:table-cell">Issued</TH>
                <TH className="hidden sm:table-cell">Valid until</TH>
                <TH>Status</TH>
                <TH align="right">Total</TH>
                <TH align="right"><span className="sr-only">PDF</span></TH>
            </THead>
            <TBody>
                {quotations.map((q) => (
                    <TR key={q.id} href={q.url}>
                        <TD className="font-medium text-slate-900">{q.number}</TD>
                        <TD className="hidden sm:table-cell">{formatDate(q.issue_date, portal.date_format)}</TD>
                        <TD className="hidden sm:table-cell">{formatDate(q.valid_until, portal.date_format)}</TD>
                        <TD><StatusBadge status={q.status} /></TD>
                        <TD align="right" className="font-medium text-slate-900">{formatMoney(q.total, portal.currency)}</TD>
                        <TD align="right"><PdfLink href={q.pdf_url} number={q.number} /></TD>
                    </TR>
                ))}
            </TBody>
        </Table>
    );
}
