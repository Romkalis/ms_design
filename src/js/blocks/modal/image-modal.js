document.addEventListener("DOMContentLoaded", function () {
	const modal = document.querySelector("#imageModal");
	if (!modal) return;

	const modalImg = modal.querySelector(".image-modal__img");
	const closeElements = modal.querySelectorAll("[data-close]");
	
	// Функция открытия модалки
	function openModal(src, alt) {
		modalImg.src = src;
		modalImg.alt = alt || "";
		modal.classList.add("is-open");
		modal.setAttribute("aria-hidden", "false");
		document.body.style.overflow = "hidden"; // Блокируем скролл страницы
	}

	// Функция закрытия модалки
	function closeModal() {
		modal.classList.remove("is-open");
		modal.setAttribute("aria-hidden", "true");
		document.body.style.overflow = ""; // Возвращаем скролл
		setTimeout(() => {
			modalImg.src = ""; // Очищаем src после анимации закрытия
		}, 300);
	}

	// Делегирование событий для открытия модалки
	document.addEventListener("click", function (e) {
		const target = e.target.closest(".js-modal-image");
		if (target) {
			e.preventDefault();
			const img = target.querySelector("img") || target;
			// Проверяем, есть ли data-src (для хайрезов), иначе берем src
			const src = target.dataset.fullSrc || img.src;
			const alt = img.alt;
			
			if (src) {
				openModal(src, alt);
			}
		}
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
