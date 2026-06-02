/**
 * assets/js/campaigns.js
 * Pre-fills the Edit Campaign modal with existing data.
 */
document.addEventListener('DOMContentLoaded', function () {
    const editModal = document.getElementById('editCampaignModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function (e) {
            const btn = e.relatedTarget;
            if (!btn) return;
            const form = editModal.querySelector('form');
            ['id','client_id','title','description','status','budget','start_date','end_date'].forEach(field => {
                const val = btn.dataset[field];
                const el  = form.querySelector('[name="' + field + '"]');
                if (el && val !== undefined) el.value = val;
            });
        });
    }

    // Edit Client modal
    const editClientModal = document.getElementById('editClientModal');
    if (editClientModal) {
        editClientModal.addEventListener('show.bs.modal', function (e) {
            const btn = e.relatedTarget;
            if (!btn) return;
            const form = editClientModal.querySelector('form');
            ['id','company_name','contact_person','email','phone','address','retainer_budget','notes'].forEach(field => {
                const val = btn.dataset[field];
                const el  = form.querySelector('[name="' + field + '"]');
                if (el && val !== undefined) el.value = val;
            });
        });
    }

    // Edit Milestone modal
    const editMilestoneModal = document.getElementById('editMilestoneModal');
    if (editMilestoneModal) {
        editMilestoneModal.addEventListener('show.bs.modal', function (e) {
            const btn = e.relatedTarget;
            if (!btn) return;
            const form = editMilestoneModal.querySelector('form');
            ['id','campaign_id','title','description','status','due_date'].forEach(field => {
                const val = btn.dataset[field];
                const el  = form.querySelector('[name="' + field + '"]');
                if (el && val !== undefined) el.value = val;
            });
        });
    }
});