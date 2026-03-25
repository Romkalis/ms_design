document.addEventListener("DOMContentLoaded", function () {
	const cookieBanner = document.getElementById("cookieBanner");
	const cookieAcceptBtn = document.getElementById("cookieAccept");

	if (!cookieBanner || !cookieAcceptBtn) return;

	// Проверяем, есть ли запись в localStorage
	const consent = localStorage.getItem("cookieConsent");

	cookieBanner.hidden = true;
	cookieBanner.classList.remove("is-visible");
	cookieBanner.setAttribute("aria-hidden", "true");

	if (!consent) {
		// Если нет, показываем баннер с небольшой задержкой
		setTimeout(() => {
			cookieBanner.hidden = false;
			cookieBanner.setAttribute("aria-hidden", "false");
			window.requestAnimationFrame(() => {
				cookieBanner.classList.add("is-visible");
			});
		}, 1000);
	}

	// Обработчик кнопки "Принять"
	cookieAcceptBtn.addEventListener("click", function () {
		// Скрываем баннер
		cookieBanner.classList.remove("is-visible");
		cookieBanner.setAttribute("aria-hidden", "true");
		
		// Сохраняем согласие в localStorage
		localStorage.setItem("cookieConsent", "true");

		window.setTimeout(() => {
			cookieBanner.hidden = true;
		}, 500);
	});
});
