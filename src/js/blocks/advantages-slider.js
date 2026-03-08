import Splide from "../../../node_modules/@splidejs/splide/";

document.addEventListener("DOMContentLoaded", function () {
	let splideInstance = null;

	function initializeSplide() {
		const splideElement = document.querySelector(".advantages");
		const listElement = document.querySelector(".advantages__list");
		const splideTrack = document.querySelector(".splide__tracker");
		const slideArrows = document.querySelector(".splide__arrows");
		// навешиваем класс перед инициализацией инстанса, анче стили перекрывают карточки

		// Проверяем ширину экрана
		if (window.innerWidth < 768) {
			// Добавляем нужные классы
			slideArrows.classList.remove("visually-hidden");
			splideTrack.classList.add("splide__track");
			splideElement.classList.add("splide");
			listElement.classList.add("splide__list");

			splideInstance = new Splide(".advantages", {
				type: "loop",
				arrows: true,
				pagination: false,
				drag: true,
				paginationKeyboard: true,
				paginationDirection: true,
				mediaQuery: "min",
				breakpoints: {
					768: {
						destroy: true,
					},
				},
			});

			const bar = splideInstance.root.querySelector(".my-carousel-progress-bar");

			const counter_start = document.querySelector(".slider__counter-start");

			// Обновляем ширину прогресс-бара при каждом движении карусели
			splideInstance.on("mounted move", function () {
				const end = splideInstance.Components.Controller.getEnd() + 1;
				const rate = Math.min((splideInstance.index + 1) / end, 1);
				bar.style.width = `${100 * rate}%`;

				// Обновляем счетчик
				counter_start.textContent = `${splideInstance.index + 1}`;
				document.querySelector(".slider__counter-end").textContent = ` / ${end}`;
			});

			splideInstance.mount();
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
	window.addEventListener("resize", initializeSplide);
});
