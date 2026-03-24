import Splide from "../../../node_modules/@splidejs/splide/";

document.addEventListener("DOMContentLoaded", function () {
	let splideInstance = null;

	function debounce(fn, wait) {
		let timeoutId = null;
		return function (...args) {
			if (timeoutId) window.clearTimeout(timeoutId);
			timeoutId = window.setTimeout(() => fn.apply(this, args), wait);
		};
	}

	function initializeSplide() {
		const splideElement = document.querySelector(".advantages");
		const listElement = document.querySelector(".advantages__list");
		const splideTrack = document.querySelector(".splide__tracker");
		const slideArrows = document.querySelector(".splide__arrows");
		// навешиваем класс перед инициализацией инстанса, анче стили перекрывают карточки
		if (!splideElement || !listElement || !splideTrack || !slideArrows) return;

		// Проверяем ширину экрана
		if (window.innerWidth < 768) {
			// Добавляем нужные классы
			slideArrows.classList.remove("visually-hidden");
			splideTrack.classList.add("splide__track");
			splideElement.classList.add("splide");
			listElement.classList.add("splide__list");

			if (!splideInstance) {
				splideInstance = new Splide(".advantages", {
					type: "loop",
					arrows: true,
					pagination: false,
					drag: true,
					paginationKeyboard: true,
					paginationDirection: true,
				});

				const bar = splideInstance.root.querySelector(".my-carousel-progress-bar");
				const counter_start = document.querySelector(".slider__counter-start");
				const counter_end = document.querySelector(".slider__counter-end");

				splideInstance.on("mounted move", function () {
					const end = splideInstance.Components.Controller.getEnd() + 1;
					const rate = Math.min((splideInstance.index + 1) / end, 1);
					if (bar) bar.style.width = `${100 * rate}%`;

					if (counter_start) counter_start.textContent = `${splideInstance.index + 1}`;
					if (counter_end) counter_end.textContent = ` / ${end}`;
				});

				splideInstance.mount();
			}
		} else {
			// Удаляем классы и уничтожаем экземпляр Splide при ширине экрана >= 768px

			splideTrack.classList.remove("splide__track");
			// на классе в библиотеке висит overflow-hidden, мешающий анимации в полноэкранном размере

			slideArrows.classList.add("visually-hidden"); // прячем стрелки слайдера
			splideElement.classList.remove("splide");
			listElement.classList.remove("splide__list");
			if (splideInstance) {
				splideInstance.destroy(true);
				splideInstance = null;
			}
		}
	}

	initializeSplide();

	// Добавляем слушатель события resize для повторной инициализации при изменении размера экрана
	window.addEventListener("resize", debounce(initializeSplide, 150));
});
