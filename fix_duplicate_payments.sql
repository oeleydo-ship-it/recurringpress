-- This SQL script will help you clean up duplicate payment records
-- Run this in your database to remove pending/failed duplicate payments

-- First, check what duplicate payments exist
SELECT id, company_id, invoice_id, payment_number, status, created_at 
FROM payments 
WHERE payment_number = 'PAY-000001'
ORDER BY created_at;

-- Delete pending or failed payments with PAY-000001 (keep only completed ones)
-- Uncomment the line below after reviewing the SELECT results above
-- DELETE FROM payments WHERE payment_number = 'PAY-000001' AND status IN ('pending', 'failed');

-- Alternative: If you want to delete ALL PAY-000001 records and start fresh
-- Uncomment the line below (WARNING: This will delete all payment records with this number)
-- DELETE FROM payments WHERE payment_number = 'PAY-000001';
