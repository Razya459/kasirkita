<?php
/**
 * Footer Template - RestoKita POS Kasir
 */
?>
<?php if (!isset($isLoginPage) || $isLoginPage !== true): ?>
        </main>
        <!-- Footer -->
        <footer class="bg-white border-top py-3 px-4 text-center text-muted small">
            RestoKita &copy; <?= date('Y') ?> &bull; Aplikasi POS Kasir Restoran untuk Uji Kompetensi Keahlian (UKK) Pemrogram Junior.
        </footer>
    </div>
</div>
<?php endif; ?>

<!-- Bootstrap 5 Bundle JS with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Script Tambahan Halaman Jika Ada -->
<?php if (isset($extraScripts) && is_array($extraScripts)): ?>
    <?php foreach ($extraScripts as $script): ?>
        <script src="<?= $script ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
