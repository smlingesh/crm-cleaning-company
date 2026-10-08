# Work Order Staff Assignment History

**Feature:** Dated staff assignments for multi-day work orders  
**Branch:** `add_staff`  
**Last updated:** October 8, 2026  

---

## Business need

Some work orders take more than one day to finish. Operations need to see:

- Who worked on a work order on each day  
- How many separate staff assignments (attempts) were made  
- Full history of supervisors and workers over time  

Previously, all staff appeared in one flat list with no work date, and rejecting a pending assignment removed **all** staff on the job.

---

## Solution overview

Each “Add Staff” save creates one **assignment batch** with:

| Field | Purpose |
|-------|---------|
| `work_date` | Calendar date the team worked on the job |
| `assignment_batch_id` | Groups staff added in one save action |
| `is_pending_approval` | Marks rows in the batch awaiting super admin approval |

The job detail page shows **assignment history** grouped by batch (newest first), with summary counts for assignments, work days, and staff entries.

---

## Database changes

**Migration:** `2026_10_08_120000_add_work_date_and_assignment_batch_to_job_staff_table.php`

**Table:** `job_staff` (existing, in `cleaning_crm`)

| Column | Type | Notes |
|--------|------|-------|
| `work_date` | date | Required for new rows; backfilled from `created_at` for existing rows |
| `assignment_batch_id` | uuid | One UUID per bulk/single add |
| `is_pending_approval` | boolean | `true` until super admin approves that batch |

**Backfill rules (existing production data):**

- `work_date` = date portion of `created_at`  
- Staff on the same job on the same calendar day share one legacy batch UUID  
- Rows on jobs in `staff_pending_approval` status: latest batch marked pending  

---

## User flows

### Add staff (telecaller / lead manager / super admin)

1. Open completed (or staff-pending) work order → **Add Staff**  
2. Select **Work Date** (defaults to today)  
3. Add supervisors/workers to the list → **Save All Staff**  
4. Job status → `staff_pending_approval` (unchanged behavior)  

### Super admin approval

| Action | Behavior |
|--------|----------|
| **Approve** | Pending batch approved (`is_pending_approval = false`); job → `completed` |
| **Reject** | Only **pending** rows deleted; **previous assignment history kept** |

### Delete individual staff

- Same permissions as before (super admin or person who added)  
- If no pending rows remain while job is `staff_pending_approval`, job returns to `completed`  

---

## UI changes

### Work order show page (`/jobs/{id}`)

- Summary pills: assignment count, work days, staff entries  
- Grouped sections: **Assignment #N · dd MMM yyyy**  
- Supervisors and workers under each assignment  
- Pending batches show **Pending approval** badge  

### Completed orders page

- Same **Work Date** field in inline Add Staff modal  
- Bulk API sends `work_date` with staff array  

---

## API / routes (unchanged URLs)

| Method | Route | Change |
|--------|-------|--------|
| POST | `/jobs/{job}/staff/bulk` | Accepts optional `work_date` (default: today) |
| POST | `/jobs/{job}/staff` | Accepts optional `work_date` |
| POST | `/jobs/{job}/staff/approve` | Approve/reject pending batch only |
| DELETE | `/jobs/{job}/staff/{staff}` | Unchanged |

---

## Files changed

| File | Change |
|------|--------|
| `database/migrations/2026_10_08_120000_add_work_date_and_assignment_batch_to_job_staff_table.php` | New columns + backfill |
| `app/Models/JobStaff.php` | Fillable + casts |
| `app/Http/Controllers/JobController.php` | `addStaff`, `bulkStore`, `approveStaff`, `deleteStaff` |
| `resources/views/jobs/show.blade.php` | History UI + work date in modal |
| `resources/views/jobs/completed.blade.php` | Work date in modal |
| `resources/views/jobs/partials/staff-member-card.blade.php` | Reusable staff row |

---

## Production deployment

```bash
# After deploy on VPS
cd /var/www/html/cleaning-crm
php artisan migrate
```

- **Downtime:** None required  
- **Risk:** Low — additive columns; backfill is non-destructive  
- **Rollback:** `php artisan migrate:rollback` (drops new columns only if no code depends on them)  

---

## Test plan

- [ ] Run migration on staging/local  
- [ ] Add staff with work date = today; verify grouped assignment on job show  
- [ ] Add second batch with different work date; verify two assignment groups and summary counts  
- [ ] Approve pending batch; verify history remains and pending badge clears  
- [ ] Reject pending batch; verify **only** new batch removed, old assignments remain  
- [ ] Add staff from Completed Orders modal with custom work date  
- [ ] Delete one staff member; permissions unchanged  

---

## Related docs

- [DEPLOYMENT_SYNC.md](./DEPLOYMENT_SYNC.md) — Production server and database summary
