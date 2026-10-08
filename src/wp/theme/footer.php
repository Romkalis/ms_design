<?php defined('ABSPATH') || exit; ?>
	@@include('../../html/blocks/footer.html')
	@@include('../../html/blocks/modal/image-modal.html')
	@@include('../../html/blocks/cookies/cookie-banner.html')
	@@include('../../html/blocks/ui/scroll-top.html')

	<script src="/js/index.bundle.js?v=<?php echo esc_attr(MSK_ASSETS_VERSION); ?>" type="module"></script>
	<?php wp_footer(); ?>
</body>

</html>
