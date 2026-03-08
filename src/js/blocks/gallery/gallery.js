import Splide from "@splidejs/splide";

document.addEventListener("DOMContentLoaded", function () {
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
					splideInstance.destroy();
					splideInstance = null;
				}
			}
		}

		// Инициализация при загрузке
		initSplide();

		// Проверка при изменении размера окна
		window.addEventListener("resize", initSplide);
	});
});
