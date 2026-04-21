document.addEventListener("DOMContentLoaded", function () {
	const modal = document.querySelector("#imageModal");
	if (!modal) return;

	const modalImg = modal.querySelector(".image-modal__img");
	const closeElements = modal.querySelectorAll("[data-close]");
	const closeButton = modal.querySelector(".image-modal__close");
	let lastActiveElement = null;
	const placeholderSrc = "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==";
	const placeholderWidth = 1;
	const placeholderHeight = 1;

	function setModalImageSize(width, height) {
		const w = Number(width);
		const h = Number(height);
		if (!Number.isFinite(w) || !Number.isFinite(h) || w <= 0 || h <= 0) return;
		modalImg.setAttribute("width", String(Math.round(w)));
		modalImg.setAttribute("height", String(Math.round(h)));
	}

	// Функция открытия модалки
	function openModal(src, alt, width, height) {
		lastActiveElement = document.activeElement;
		modalImg.src = src;
		modalImg.alt = alt || "";
		setModalImageSize(width, height);
		modal.classList.add("is-open");
		modal.setAttribute("aria-hidden", "false");
		document.body.style.overflow = "hidden"; // Блокируем скролл страницы
		if (closeButton) closeButton.focus();
	}

	// Функция закрытия модалки
	function closeModal() {
		modal.classList.remove("is-open");
		modal.setAttribute("aria-hidden", "true");
		document.body.style.overflow = ""; // Возвращаем скролл
		setTimeout(() => {
			modalImg.src = placeholderSrc; // Очищаем src после анимации закрытия
			setModalImageSize(placeholderWidth, placeholderHeight);
		}, 300);
		if (lastActiveElement && typeof lastActiveElement.focus === "function") {
			lastActiveElement.focus();
		}
	}

	document.querySelectorAll(".js-modal-image").forEach((el) => {
		if (!el.hasAttribute("tabindex")) el.setAttribute("tabindex", "0");
		if (!el.hasAttribute("role")) el.setAttribute("role", "button");
		if (!el.hasAttribute("title")) el.setAttribute("title", "Открыть изображение");
		if (!el.hasAttribute("aria-label")) el.setAttribute("aria-label", "Открыть изображение");
	});

	// Делегирование событий для открытия модалки
	document.addEventListener("click", function (e) {
		const target = e.target.closest(".js-modal-image");
		if (target) {
			e.preventDefault();
			const img = target.querySelector("img") || target;
			// Проверяем, есть ли data-src (для хайрезов), иначе берем src
			const src = target.dataset.fullSrc || img.src;
			const alt = img.alt;
			const width = img.naturalWidth || img.width;
			const height = img.naturalHeight || img.height;

			if (src) {
				openModal(src, alt, width, height);
			}
		}
	});

	document.addEventListener("keydown", function (e) {
		if (e.key !== "Enter" && e.key !== " ") return;
		const target = e.target.closest(".js-modal-image");
		if (!target) return;
		e.preventDefault();
		const img = target.querySelector("img") || target;
		const src = target.dataset.fullSrc || img.src;
		const alt = img.alt;
		const width = img.naturalWidth || img.width;
		const height = img.naturalHeight || img.height;
		if (src) openModal(src, alt, width, height);
	});

	// Обработчики закрытия
	closeElements.forEach((el) => {
		el.addEventListener("click", closeModal);
	});

	// Закрытие по Esc
	document.addEventListener("keydown", function (e) {
		if (e.key === "Escape" && modal.classList.contains("is-open")) {
			closeModal();
		}
	});
});
