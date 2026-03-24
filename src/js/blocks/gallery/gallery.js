import Splide from "@splidejs/splide";

document.addEventListener("DOMContentLoaded", function () {
	function debounce(fn, wait) {
		let timeoutId = null;
		return function (...args) {
			if (timeoutId) window.clearTimeout(timeoutId);
			timeoutId = window.setTimeout(() => fn.apply(this, args), wait);
		};
	}

	function cleanupSplide(root) {
		if (!root) return;
		root.classList.remove("is-active", "is-initialized", "is-rendered");
		root.querySelectorAll(".splide__track, .splide__list, .splide__slide").forEach((el) => {
			el.removeAttribute("style");
			el.removeAttribute("aria-hidden");
			el.removeAttribute("tabindex");
		});
	}

	const resizeHandlers = [];

	// Находим все секции галереи
	const gallerySections = document.querySelectorAll(".gallery__section");

	gallerySections.forEach((section) => {
		const sliderElement = section.querySelector(".splide");
		if (!sliderElement) return;

		let splideInstance = null;

		function initSplide() {
			if (window.innerWidth < 820) {
				if (!splideInstance) {
					splideInstance = new Splide(sliderElement, {
						type: "loop",
						perPage: 1,
						pagination: true,
						arrows: true,
						gap: "20px",
					});
					splideInstance.mount();
				}
			} else {
				if (splideInstance) {
					splideInstance.destroy(true);
					splideInstance = null;
				}
				cleanupSplide(sliderElement);
			}
		}

		// Инициализация при загрузке
		resizeHandlers.push(initSplide);
	});

	const worksSliders = document.querySelectorAll(".works__slider");

	worksSliders.forEach((sliderElement) => {
		let splideInstance = null;

		function initSplide() {
			if (window.innerWidth < 768) {
				if (!splideInstance) {
					splideInstance = new Splide(sliderElement, {
						type: "loop",
						perPage: 1,
						pagination: true,
						arrows: true,
						gap: "20px",
					});
					splideInstance.mount();
				}
			} else {
				if (splideInstance) {
					splideInstance.destroy(true);
					splideInstance = null;
				}
				cleanupSplide(sliderElement);
			}
		}

		resizeHandlers.push(initSplide);
	});

	resizeHandlers.forEach((fn) => fn());
	window.addEventListener(
		"resize",
		debounce(() => {
			resizeHandlers.forEach((fn) => fn());
		}, 150)
	);
});
