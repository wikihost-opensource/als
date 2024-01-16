import { zhCN, dateZhCN, enUS, dateEnUS } from 'naive-ui'
import { nextTick } from 'vue'
import { createI18n } from 'vue-i18n'

export const list = [
  {
    label: '简体中文',
    value: 'zh-CN',
    autoChangeMap: ['zh-CN', 'zh'],
    uiLang: () => zhCN,
    dateLang: () => dateZhCN
  },
  {
    label: 'English',
    value: 'en-US',
    autoChangeMap: ['en-US', 'en'],
    uiLang: () => enUS,
    dateLang: () => dateEnUS
  }
]

const locales = list.map((x) => x.value)
const i18n = createI18n({
  locale: locales[0],
  legacy: false
})

// copy from https://vue-i18n.intlify.dev/guide/advanced/lazy.html
export function setupI18n() {
  loadLocaleMessages(locales[0])
  setI18nLanguage(locales[0])

  return i18n
}

export function setI18nLanguage(locale) {
  if (i18n.mode === 'legacy') {
    i18n.global.locale = locale
  } else {
    i18n.global.locale.value = locale
  }
  /**
   * NOTE:
   * If you need to specify the language setting for headers, such as the `fetch` API, set it here.
   * The following is an example for axios.
   *
   * axios.defaults.headers.common['Accept-Language'] = locale
   */
  document.querySelector('html').setAttribute('lang', locale)
}

export async function loadLocaleMessages(locale) {
  // load locale messages with dynamic import
  const messages = await import(`../locales/${locale}.json`)

  console.log(messages.default)
  // set locale and locale message
  i18n.global.setLocaleMessage(locale, messages.default)

  return nextTick()
}

export async function autoLang() {
  for (var index in list) {
    const lang = list[index]
    if (lang.autoChangeMap.indexOf(navigator.language) != -1) {
      await loadLocaleMessages(lang.value)
      setI18nLanguage(lang.value)
      return lang.value
    }
  }
}
