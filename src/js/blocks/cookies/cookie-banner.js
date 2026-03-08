document.addEventListener("DOMContentLoaded", function () {
	const cookieBanner = document.getElementById("cookieBanner");
	const cookieAcceptBtn = document.getElementById("cookieAccept");
	
	if (!cookieBanner || !cookieAcceptBtn) return;

	// Проверяем, есть ли запись в localStorage
	const consent = localStorage.getItem("cookieConsent");

	if (!consent) {
		// Если нет, показываем баннер с небольшой задержкой
		setTimeout(() => {
			cookieBanner.classList.add("is-visible");
			cookieBanner.setAttribute("aria-hidden", "false");
		}, 1000);
	}

	// Обработчик кнопки "Принять"
	cookieAcceptBtn.addEventListener("click", function () {
		// Скрываем баннер
		cookieBanner.classList.remove("is-visible");
		cookieBanner.setAttribute("aria-hidden", "true");
		
		// Сохраняем согласие в localStorage
		localStorage.setItem("cookieConsent", "true");
	});
});
