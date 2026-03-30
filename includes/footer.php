<?php // includes/footer.php ?>
        <footer>
            <div class="footer-brand">
                <span>APX</span> AutoMai &mdash; Admin Portal &copy; <?php echo date('Y'); ?>
            </div>
            <div>
                <a href="#">Privacy Policy</a>
                &nbsp;&middot;&nbsp;
                <a href="#">Terms &amp; Conditions</a>
            </div>
        </footer>
    </div><!-- /.main-content -->
</div><!-- /.layout -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
<script>
// Sidebar toggle
const sidebar = document.getElementById('sidebar');
const mainContent = document.getElementById('mainContent');
document.getElementById('sidebarToggle').addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('expanded');
});

// Sub-nav toggle
function toggleSub(e, id, link) {
    e.preventDefault();
    const sub = document.getElementById(id);
    const isOpen = sub.classList.contains('open');
    document.querySelectorAll('.sub-nav').forEach(s => s.classList.remove('open'));
    document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('open'));
    if (!isOpen) { sub.classList.add('open'); link.classList.add('open'); }
}

// Modal helpers
function openModal(id)  { document.getElementById(id).classList.add('open');    document.body.style.overflow='hidden'; }
function closeModal(id) { document.getElementById(id).classList.remove('open'); document.body.style.overflow=''; }
document.querySelectorAll('.modal-overlay').forEach(m => {
    m.addEventListener('click', e => { if(e.target===m) closeModal(m.id); });
});
</script>