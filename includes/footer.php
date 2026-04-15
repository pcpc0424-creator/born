            </div><!-- .admin-content -->
        </main><!-- .admin-main -->
    </div><!-- .admin-layout -->

    <script src="/assets/js/admin.js"></script>
    <?php if (isset($additionalJs)): ?>
        <?php foreach ($additionalJs as $js): ?>
            <script src="<?= h($js) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
