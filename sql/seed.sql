-- ===========================================================
-- CharterDesk - demo data
-- Run after schema.sql:  mysql -u root < sql/seed.sql
-- Creates one negotiation with a firm offer + an accepted counter,
-- so the dashboard, timeline, version-diff and recap/CP have content.
-- ===========================================================

USE ime_negotiation;

INSERT INTO threads (thread_uuid, created_by, status, locked_fields, created_at)
VALUES ('th_demo001', 'Demo Owner', 'open', '["cargo","load_port"]', NOW());

-- v1: opening firm offer from the owner
INSERT INTO offers (thread_uuid, version, party, role, data, created_at) VALUES
('th_demo001', 1, 'Demo Owner', 'Ship Owner',
 '{"role":"Ship Owner","vessel":"MV Demo Star","cargo":"Steam Coal","qty":"55,000 MT +/-10% MOLOO","load_port":"Kandla - Adani Terminal","dis_port":"Port Qasim - Fauji Terminal","laycan_start":"15 Oct 2025","laycan_end":"20 Oct 2025","freight":"USD 6.50 PMT FIOST","demurrage":"USD 12,000 PDPR","despatch":"USD 6,000 PDPR","laytime":"Reversible","cp_base":"GENCON 1994"}',
 NOW());

-- v2: charterer counter (freight + demurrage moved), then accepted
INSERT INTO offers (thread_uuid, version, party, role, data, accepted_by, accepted_at, created_at) VALUES
('th_demo001', 2, 'Demo Charterer', 'Charterer',
 '{"role":"Charterer","vessel":"MV Demo Star","cargo":"Steam Coal","qty":"55,000 MT +/-10% MOLOO","load_port":"Kandla - Adani Terminal","dis_port":"Port Qasim - Fauji Terminal","laycan_start":"15 Oct 2025","laycan_end":"20 Oct 2025","freight":"USD 6.10 PMT FIOST","demurrage":"USD 10,000 PDPR","despatch":"USD 5,000 PDPR","laytime":"Reversible","cp_base":"GENCON 1994"}',
 'Demo Owner', NOW(), NOW());
