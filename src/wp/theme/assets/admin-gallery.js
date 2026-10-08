(function ($) {
	$(function () {
		const $box = $(".msk-gallery");
		if (!$box.length) return;

		const $list = $box.find(".msk-gallery__list");
		const $input = $box.find('input[name="msk_gallery"]');
		let frame = null;

		function sync() {
			const ids = $list
				.children()
				.map(function () {
					return this.dataset.id;
				})
				.get();
			$input.val(ids.join(","));
			$box.toggleClass("is-empty", ids.length === 0);
		}

		function createItem(attachment) {
			const size = attachment.sizes && (attachment.sizes.thumbnail || attachment.sizes.medium || attachment.sizes.full);
			const url = size ? size.url : attachment.url;
			return $('<li class="msk-gallery__item">')
				.attr("data-id", attachment.id)
				.append($("<img>").attr({src: url, alt: ""}))
				.append('<button type="button" class="msk-gallery__remove" aria-label="Убрать фото">&times;</button>');
		}

		$list.sortable({
			items: "> li",
			tolerance: "pointer",
			cursor: "move",
			update: sync,
		});

		$list.on("click", ".msk-gallery__remove", function () {
			$(this).closest("li").remove();
			sync();
		});

		$box.on("click", ".msk-gallery__add", function (e) {
			e.preventDefault();
			if (!frame) {
				frame = wp.media({
					title: "Добавить фото в проект",
					button: {text: "Добавить в проект"},
					library: {type: "image"},
					multiple: "add",
				});
				frame.on("open", function () {
					frame.state().get("selection").reset();
				});
				frame.on("select", function () {
					frame
						.state()
						.get("selection")
						.each(function (model) {
							const attachment = model.toJSON();
							if (!$list.children('[data-id="' + attachment.id + '"]').length) {
								$list.append(createItem(attachment));
							}
						});
					sync();
				});
			}
			frame.open();
		});

		$box.on("click", ".msk-gallery__clear", function (e) {
			e.preventDefault();
			if (!$list.children().length) return;
			if (!window.confirm("Убрать все фото из проекта? Из медиатеки они не удалятся.")) return;
			$list.empty();
			sync();
		});
	});
})(jQuery);
