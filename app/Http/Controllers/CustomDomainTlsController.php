<?php

namespace App\Http\Controllers;

use App\Support\TenantDomain;
use Illuminate\Http\Request;

/**
 * "Ask" endpoint for on-demand TLS (e.g. Caddy's on_demand_tls): the proxy calls
 * /tls/allowed?domain=<host> before requesting a certificate, and only gets a 200 for the
 * platform's own hosts and verified tenant domains. This stops anyone pointing random
 * domains at the server and making it request certificates for them.
 */
class CustomDomainTlsController extends Controller
{
    public function __invoke(Request $request)
    {
        $domain = TenantDomain::normalize((string) $request->query('domain'));

        $allowed = $domain && (in_array($domain, TenantDomain::platformHosts(), true) || TenantDomain::companyForHost($domain));

        return response($allowed ? 'OK' : 'Not allowed', $allowed ? 200 : 404, ['Content-Type' => 'text/plain']);
    }
}
