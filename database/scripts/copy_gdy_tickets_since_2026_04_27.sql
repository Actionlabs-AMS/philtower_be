-- One-off: copy ticket_requests (created_at >= 2026-04-27) from philtower-gdy into philtower
-- with correct remapped ticket_request_id for sla_clocks and ticket_updates.
-- Safe to re-run: uses NOT EXISTS guards on natural keys.

START TRANSACTION;

-- Categories required for FKs (dependency order: parents before children)
INSERT INTO philtower.categories (id, name, code, descriptions, parent_id, active, created_at, updated_at, deleted_at)
SELECT g.id, g.name, g.code, g.descriptions, g.parent_id, g.active, g.created_at, g.updated_at, g.deleted_at
FROM `philtower-gdy`.categories g
WHERE g.id IN (9, 13, 19)
  AND NOT EXISTS (SELECT 1 FROM philtower.categories c WHERE c.id = g.id);

INSERT INTO philtower.categories (id, name, code, descriptions, parent_id, active, created_at, updated_at, deleted_at)
SELECT g.id, g.name, g.code, g.descriptions, g.parent_id, g.active, g.created_at, g.updated_at, g.deleted_at
FROM `philtower-gdy`.categories g
WHERE g.id IN (7, 8)
  AND NOT EXISTS (SELECT 1 FROM philtower.categories c WHERE c.id = g.id);

INSERT INTO philtower.categories (id, name, code, descriptions, parent_id, active, created_at, updated_at, deleted_at)
SELECT g.id, g.name, g.code, g.descriptions, g.parent_id, g.active, g.created_at, g.updated_at, g.deleted_at
FROM `philtower-gdy`.categories g
WHERE g.id = 10
  AND NOT EXISTS (SELECT 1 FROM philtower.categories c WHERE c.id = g.id);

INSERT INTO philtower.categories (id, name, code, descriptions, parent_id, active, created_at, updated_at, deleted_at)
SELECT g.id, g.name, g.code, g.descriptions, g.parent_id, g.active, g.created_at, g.updated_at, g.deleted_at
FROM `philtower-gdy`.categories g
WHERE g.id IN (14, 15)
  AND NOT EXISTS (SELECT 1 FROM philtower.categories c WHERE c.id = g.id);

INSERT INTO philtower.categories (id, name, code, descriptions, parent_id, active, created_at, updated_at, deleted_at)
SELECT g.id, g.name, g.code, g.descriptions, g.parent_id, g.active, g.created_at, g.updated_at, g.deleted_at
FROM `philtower-gdy`.categories g
WHERE g.id = 20
  AND NOT EXISTS (SELECT 1 FROM philtower.categories c WHERE c.id = g.id);

-- Item referenced by tickets but missing in philtower
INSERT INTO philtower.items (id, name, code, description, subcategory_id, active, created_at, updated_at, deleted_at)
SELECT g.id, g.name, g.code, g.description, g.subcategory_id, g.active, g.created_at, g.updated_at, g.deleted_at
FROM `philtower-gdy`.items g
WHERE g.id = 73
  AND NOT EXISTS (SELECT 1 FROM philtower.items i WHERE i.id = g.id);

-- New ticket rows (omit id: philtower ids already mean different tickets)
INSERT INTO philtower.ticket_requests (
  request_number, user_id, created_by, parent_ticket_id, service_type_id,
  category_id, subcategory_id, item_id, description, attachment_metadata,
  contact_number, contact_name, contact_email, ticket_status_id, slas_id,
  ticket_priority_id, for_approval, manual_approval_data, assigned_to,
  submitted_at, resolved_at, closed_at, csat_token, csat_rating,
  created_at, updated_at, deleted_at
)
SELECT
  g.request_number, g.user_id, g.created_by, g.parent_ticket_id, g.service_type_id,
  g.category_id, g.subcategory_id, g.item_id, g.description, g.attachment_metadata,
  g.contact_number, g.contact_name, g.contact_email, g.ticket_status_id, g.slas_id,
  g.ticket_priority_id, g.for_approval, g.manual_approval_data, g.assigned_to,
  g.submitted_at, g.resolved_at, g.closed_at, g.csat_token, g.csat_rating,
  g.created_at, g.updated_at, g.deleted_at
FROM `philtower-gdy`.ticket_requests g
WHERE g.created_at >= '2026-04-27 00:00:00'
  AND NOT EXISTS (SELECT 1 FROM philtower.ticket_requests p WHERE p.request_number = g.request_number);

-- SLA clocks: remap entity_id to new philtower.ticket_requests.id via request_number
INSERT INTO philtower.sla_clocks (
  entity_type, entity_id, sla_id, started_at, due_at, response_due_at,
  paused_at, total_paused_minutes, breached_at, completed_at, status,
  created_at, updated_at
)
SELECT
  c.entity_type,
  p.id,
  c.sla_id, c.started_at, c.due_at, c.response_due_at,
  c.paused_at, c.total_paused_minutes, c.breached_at, c.completed_at, c.status,
  c.created_at, c.updated_at
FROM `philtower-gdy`.sla_clocks c
INNER JOIN `philtower-gdy`.ticket_requests g ON g.id = c.entity_id AND c.entity_type = 'ticket_request'
INNER JOIN philtower.ticket_requests p ON p.request_number = g.request_number
WHERE g.created_at >= '2026-04-27 00:00:00'
  AND NOT EXISTS (
    SELECT 1 FROM philtower.sla_clocks pc
    WHERE pc.entity_type = 'ticket_request'
      AND pc.entity_id = p.id
      AND pc.started_at <=> c.started_at
      AND pc.sla_id = c.sla_id
  );

-- Ticket updates: remap ticket_request_id; parent_update_id is null for this batch
INSERT INTO philtower.ticket_updates (
  ticket_request_id, parent_update_id, user_id, content, type, metadata,
  is_internal, created_by, updated_by, created_at, updated_at, deleted_at
)
SELECT
  p.id,
  tu.parent_update_id,
  tu.user_id, tu.content, tu.type, tu.metadata,
  tu.is_internal, tu.created_by, tu.updated_by, tu.created_at, tu.updated_at, tu.deleted_at
FROM `philtower-gdy`.ticket_updates tu
INNER JOIN `philtower-gdy`.ticket_requests g ON g.id = tu.ticket_request_id
INNER JOIN philtower.ticket_requests p ON p.request_number = g.request_number
WHERE g.created_at >= '2026-04-27 00:00:00'
  AND NOT EXISTS (
    SELECT 1 FROM philtower.ticket_updates ptu
    WHERE ptu.ticket_request_id = p.id
      AND ptu.created_at <=> tu.created_at
      AND ptu.type = tu.type
      AND ptu.content <=> tu.content
  );

COMMIT;
