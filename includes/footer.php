        </main>
        <footer class="app-footer">
            <span>&copy; <?= date('Y') ?> MAGERWA. All rights reserved.</span>
            <span class="system-status"><i class="bi bi-circle-fill"></i>All systems operational</span>
            <span><?= date('M d, Y') ?></span>
        </footer>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('.alert-success, .alert-info').forEach((alertElement) => {
    setTimeout(() => {
        const alert = bootstrap.Alert.getOrCreateInstance(alertElement);
        alert.close();
    }, 3500);
});
</script>
</body>
</html>
