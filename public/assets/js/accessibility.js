(function () {
  const body = document.body;
  const fontButtons = document.querySelectorAll('[data-font-scale]');
  const screenReaderButton = document.querySelector('[data-screen-reader-toggle]');
  const fontScaleClasses = ['font-scale-sm', 'font-scale-lg', 'font-scale-xl'];
  const scaleToClass = {
    sm: 'font-scale-sm',
    lg: 'font-scale-lg',
    xl: 'font-scale-xl',
  };

  const applyScale = (scale) => {
    body.classList.remove(...fontScaleClasses);
    const targetClass = scaleToClass[scale];
    if (targetClass) {
      body.classList.add(targetClass);
    }
  };

  const setPressed = (activeButton) => {
    fontButtons.forEach((btn) =>
      btn.setAttribute('aria-pressed', btn === activeButton ? 'true' : 'false')
    );
  };

  fontButtons.forEach((button) => {
    button.addEventListener('click', () => {
      const scale = button.dataset.fontScale || 'md';
      if (scale === 'lg') {
        let nextScale = 'lg';
        if (body.classList.contains('font-scale-lg')) {
          nextScale = 'xl';
        } else if (body.classList.contains('font-scale-xl')) {
          nextScale = 'xl';
        }
        setPressed(button);
        applyScale(nextScale);
        return;
      }
      setPressed(button);
      applyScale(scale);
    });
  });

  screenReaderButton?.setAttribute('disabled', 'true');
})();
