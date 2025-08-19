// register.js
(() => {
  "use strict";

  document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector("form[method='POST']");
    const usernameInput = document.getElementById("username");
    const passwordInput = document.getElementById("password");
    const submitBtn = form.querySelector("button[type='submit']");

    // Crea contenedores de error si no existen
    const ensureErrorEl = (input) => {
      let el = input.parentElement.querySelector(".field-error");
      if (!el) {
        el = document.createElement("div");
        el.className = "field-error";
        el.setAttribute("aria-live", "polite");
        el.style.color = "red";
        el.style.fontSize = "0.9rem";
        el.style.marginTop = "0.35rem";
        input.parentElement.appendChild(el);
      }
      return el;
    };

    const showError = (input, message) => {
      const el = ensureErrorEl(input);
      el.textContent = message || "";
      input.setAttribute("aria-invalid", message ? "true" : "false");
    };

    const sanitize = (str) =>
      str
        .normalize("NFKC")       // evita caracteres raros equivalentes
        .replace(/\s+/g, " ")    // colapsa espacios múltiples
        .trim();

    // Reglas de validación
    const validateUsername = () => {
      const raw = usernameInput.value;
      const value = sanitize(raw);
      if (raw !== value) usernameInput.value = value;

      // 3–20 caracteres, empieza por letra, permite letras/números/_ y sin "__"
      if (value.length === 0) {
        showError(usernameInput, "El nombre de usuario es obligatorio.");
        return false;
      }
      if (value.length < 3 || value.length > 20) {
        showError(usernameInput, "Debe tener entre 3 y 20 caracteres.");
        return false;
      }
      if (!/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]/.test(value)) {
        showError(usernameInput, "Debe comenzar por una letra.");
        return false;
      }
      if (!/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ0-9_]+$/.test(value)) {
        showError(usernameInput, "Solo letras, números y guiones bajos (_).");
        return false;
      }
      if (/__/.test(value)) {
        showError(usernameInput, "Evita guiones bajos consecutivos (__).");
        return false;
      }
      showError(usernameInput, "");
      return true;
    };

    const validatePassword = () => {
      const value = passwordInput.value;

      if (!value) {
        showError(passwordInput, "La contraseña es obligatoria.");
        return false;
      }
      if (value.length < 8 || value.length > 72) {
        showError(
          passwordInput,
          "Debe tener entre 8 y 72 caracteres."
        );
        return false;
      }
      if (/\s/.test(value)) {
        showError(passwordInput, "No puede contener espacios.");
        return false;
      }
      if (!/[a-z]/.test(value)) {
        showError(passwordInput, "Debe incluir al menos una minúscula.");
        return false;
      }
      if (!/[A-Z]/.test(value)) {
        showError(passwordInput, "Debe incluir al menos una mayúscula.");
        return false;
      }
      if (!/[0-9]/.test(value)) {
        showError(passwordInput, "Debe incluir al menos un número.");
        return false;
      }
      if (!/[^\w\s]/.test(value)) {
        showError(
          passwordInput,
          "Debe incluir al menos un símbolo (p. ej., !@#€$%)."
        );
        return false;
      }
      // Evita que la contraseña sea igual al usuario
      if (
        usernameInput.value &&
        value.toLowerCase().includes(usernameInput.value.toLowerCase())
      ) {
        showError(passwordInput, "No reutilices el nombre de usuario en la contraseña.");
        return false;
      }

      showError(passwordInput, "");
      return true;
    };

    const allValid = () => validateUsername() & validatePassword(); // usa bitwise para evaluar ambas

    // Desactiva/activa el submit según la validez
    const updateSubmitState = () => {
      submitBtn.disabled = !allValid();
      submitBtn.style.opacity = submitBtn.disabled ? "0.6" : "1";
      submitBtn.style.cursor = submitBtn.disabled ? "not-allowed" : "pointer";
    };

    // Validación en vivo con debounce para no ser intrusivos
    const debounce = (fn, ms = 200) => {
      let t;
      return (...args) => {
        clearTimeout(t);
        t = setTimeout(() => fn.apply(null, args), ms);
      };
    };

    const onUsernameInput = debounce(() => {
      validateUsername();
      updateSubmitState();
    }, 150);

    const onPasswordInput = debounce(() => {
      validatePassword();
      updateSubmitState();
    }, 150);

    usernameInput.addEventListener("input", onUsernameInput);
    usernameInput.addEventListener("blur", () => {
      validateUsername();
      updateSubmitState();
    });

    passwordInput.addEventListener("input", onPasswordInput);
    passwordInput.addEventListener("blur", () => {
      validatePassword();
      updateSubmitState();
    });

    // Evita pegar espacios por error
    passwordInput.addEventListener("paste", (e) => {
      const pasted = (e.clipboardData || window.clipboardData).getData("text");
      if (/\s/.test(pasted)) e.preventDefault();
    });

    // Estado inicial
    updateSubmitState();

    // Validación final al enviar
    form.addEventListener("submit", (e) => {
      const ok = allValid();
      if (!ok) {
        e.preventDefault();
        // Lleva el foco al primer campo con error
        if (usernameInput.getAttribute("aria-invalid") === "true") {
          usernameInput.focus();
        } else if (passwordInput.getAttribute("aria-invalid") === "true") {
          passwordInput.focus();
        }
      }
      // Nota: el servidor ya hace hash de la contraseña; no se hace nada aquí.
    });
  });
})();
