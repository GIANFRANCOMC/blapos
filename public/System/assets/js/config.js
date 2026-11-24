/**
 * Config
 * -------------------------------------------------------------------------------------
 * ! IMPORTANT: Make sure you clear the browser local storage In order to see the config changes in the template.
 * ! To clear local storage: (https://www.leadshook.com/help/how-to-clear-local-storage-in-google-chrome-browser/).
 */

'use strict';

const fgRoot = document.documentElement;
const fgRootStyles = window.getComputedStyle(fgRoot);

const fgCssToken = (name, fallback = '') => fgRootStyles.getPropertyValue(name).trim() || fallback;

const fgColorWithAlpha = (color, alphaHex = '29') => {
  const normalized = color.trim();
  const shortHex = normalized.match(/^#([0-9a-f]{3})$/i);
  const longHex = normalized.match(/^#([0-9a-f]{6})$/i);

  if (longHex) return `${normalized}${alphaHex}`;

  if (shortHex) {
    const expanded = shortHex[1]
      .split('')
      .map(part => part + part)
      .join('');

    return `#${expanded}${alphaHex}`;
  }

  const alphaPercent = Math.round((parseInt(alphaHex, 16) / 255) * 100);

  return `color-mix(in srgb, ${normalized} ${alphaPercent}%, transparent)`;
};

const fgNormalizeAssetsPath = value => {
  const fallback = '/System/assets/';
  const source = (value || fallback).trim();
  const isAbsoluteUrl = /^[a-z][a-z\d+\-.]*:\/\//i.test(source) || source.startsWith('//');

  if (isAbsoluteUrl || source.startsWith('/')) {
    return new URL(source, window.location.origin).href.replace(/\/?$/, '/');
  }

  const systemAssetsMatch = source.match(/(?:^|\/)(System\/assets\/?.*)$/i);

  if (systemAssetsMatch) {
    return new URL(`/${systemAssetsMatch[1].replace(/^\/+/, '')}`, window.location.origin).href.replace(/\/?$/, '/');
  }

  return new URL(source, window.location.href).href.replace(/\/?$/, '/');
};

// JS global variables
let config = {
  typography: {
    get fontFamily() { return fgCssToken("--fg-font", "\"Source Sans 3\", sans-serif"); },
  },
  colors: {
    get primary() { return fgCssToken('--fg-primary'); },
    get secondary() { return fgCssToken('--fg-heading'); },
    get success() { return fgCssToken('--fg-success'); },
    get info() { return fgCssToken('--fg-info'); },
    get warning() { return fgCssToken('--fg-warning'); },
    get danger() { return fgCssToken('--fg-danger'); },
    get dark() { return fgCssToken('--fg-neutral-900'); },
    get black() { return fgCssToken('--fg-black'); },
    get white() { return fgCssToken('--fg-surface'); },
    get cardColor() { return fgCssToken('--fg-surface'); },
    get bodyBg() { return fgCssToken('--fg-canvas'); },
    get bodyColor() { return fgCssToken('--fg-text'); },
    get headingColor() { return fgCssToken('--fg-heading'); },
    get textMuted() { return fgCssToken('--fg-muted'); },
    get borderColor() { return fgCssToken('--fg-border'); },
  },
  colors_label: {
    get primary() { return fgColorWithAlpha(fgCssToken('--fg-primary')); },
    get secondary() { return fgColorWithAlpha(fgCssToken('--fg-heading')); },
    get success() { return fgColorWithAlpha(fgCssToken('--fg-success')); },
    get info() { return fgColorWithAlpha(fgCssToken('--fg-info')); },
    get warning() { return fgColorWithAlpha(fgCssToken('--fg-warning')); },
    get danger() { return fgColorWithAlpha(fgCssToken('--fg-danger')); },
    get dark() { return fgColorWithAlpha(fgCssToken('--fg-neutral-900')); },
  },
  colors_dark: {
    get cardColor() { return fgCssToken('--fg-secondary-hover'); },
    get bodyBg() { return fgCssToken('--fg-secondary-active'); },
    get bodyColor() { return fgCssToken('--fg-secondary-soft'); },
    get headingColor() { return fgCssToken('--fg-surface'); },
    get textMuted() { return fgCssToken('--fg-secondary-muted'); },
    get borderColor() { return fgCssToken('--fg-template-dark-border'); },
  },
  enableMenuLocalStorage: true // Enable menu state with local storage support
};

let assetsPath = fgNormalizeAssetsPath(fgRoot.getAttribute('data-assets-path')),
  templateName = document.documentElement.getAttribute('data-template'),
  rtlSupport = true; // set true for rtl support (rtl + ltr), false for ltr only.

fgRoot.setAttribute('data-assets-path', assetsPath);
window.assetsPath = assetsPath;

/**
 * TemplateCustomizer
 * ! You must use(include) template-customizer.js to use TemplateCustomizer settings
 * -----------------------------------------------------------------------------------------------
 */

// To use more themes, just push it to THEMES object.

/* TemplateCustomizer.THEMES.push({
  name: 'theme-raspberry',
  title: 'Raspberry'
}); */

// To add more languages, just push it to LANGUAGES object.
/*
TemplateCustomizer.LANGUAGES.fr = { ... };
*/

/**
 * TemplateCustomizer settings
 * -------------------------------------------------------------------------------------
 * cssPath: Core CSS file path
 * themesPath: Theme CSS file path
 * displayCustomizer: true(Show customizer), false(Hide customizer)
 * lang: To set default language, Add more langues and set default. Fallback language is 'en'
 * controls: [ 'rtl', 'style', 'headerType', 'contentLayout', 'layoutCollapsed', 'layoutNavbarOptions', 'themes' ] | Show/Hide customizer controls
 * defaultTheme: 0(Default), 1(Bordered), 2(Semi Dark)
 * defaultStyle: 'light', 'dark', 'system' (Mode)
 * defaultTextDir: 'ltr', 'rtl' (rtlSupport must be true for rtl mode)
 * defaultContentLayout: 'compact', 'wide' (compact=container-xxl, wide=container-fluid)
 * defaultHeaderType: 'static', 'fixed' (for horizontal layout only)
 * defaultMenuCollapsed: true, false (For vertical layout only)
 * defaultNavbarType: 'sticky', 'static', 'hidden' (For vertical layout only)
 * defaultFooterFixed: true, false (For vertical layout only)
 * defaultShowDropdownOnHover : true, false (for horizontal layout only)
 */

if (typeof TemplateCustomizer !== 'undefined') {
  window.templateCustomizer = new TemplateCustomizer({
    cssPath: assetsPath + 'vendor/css' + (rtlSupport ? '/rtl' : '') + '/',
    themesPath: assetsPath + 'vendor/css' + (rtlSupport ? '/rtl' : '') + '/',
    displayCustomizer: false,
    lang: localStorage.getItem('templateCustomizer-' + templateName + '--Lang') || 'en', // Set default language here
    defaultTheme: 2,
    defaultStyle: 'light',
    // defaultTextDir: 'rtl',
    // defaultContentLayout: 'wide',
    // defaultHeaderType: 'static',
    // defaultMenuCollapsed: true,
    // defaultNavbarType: 'sticky',
    // defaultFooterFixed: false,
    // defaultShowDropdownOnHover: false,
    controls: ['rtl', 'style', 'headerType', 'contentLayout', 'layoutCollapsed', 'layoutNavbarOptions', 'themes']
  });
}
