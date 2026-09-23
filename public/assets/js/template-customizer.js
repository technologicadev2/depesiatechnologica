import customizerStyle from './_template-customizer/_template-customizer.scss'
import customizerMarkup from './_template-customizer/_template-customizer.html'

const CSS_FILENAME_PATTERN = '%name%.css'
const CONTROLS = [
  'rtl',
  'style',
  'layoutType',
  'layoutMenuFlipped',
  'showDropdownOnHover',
  'layoutNavbarFixed',
  'layoutFooterFixed',
  'themes'
]
const STYLES = ['light', 'dark']

const cl = document.documentElement.classList

const DISPLAY_CUSTOMIZER = true
const DEFAULT_THEME = document.getElementsByTagName('HTML')[0].getAttribute('data-theme') || 0
const DEFAULT_STYLE = cl.contains('dark-style') ? 'dark' : 'light'
const DEFAULT_TEXT_DIR = document.documentElement.getAttribute('dir') === 'rtl'
const DEFAULT_MENU_COLLAPSED = !!cl.contains('layout-menu-collapsed')
const DEFAULT_MENU_FLIPPED = !!cl.contains('layout-menu-flipped')
const DEFAULT_SHOW_DROPDOWN_ON_HOVER = undefined
const DEFAULT_NAVBAR_FIXED = !!cl.contains('layout-navbar-fixed')
const DEFAULT_FOOTER_FIXED = !!cl.contains('layout-footer-fixed')

let layoutType
if (cl.contains('layout-menu-offcanvas')) {
  layoutType = 'static-offcanvas'
} else if (cl.contains('layout-menu-fixed')) {
  layoutType = 'fixed'
} else if (cl.contains('layout-menu-fixed-offcanvas')) {
  layoutType = 'fixed-offcanvas'
} else {
  layoutType = 'static'
}
const DEFAULT_LAYOUT_TYPE = layoutType

TemplateCustomizer.LANGUAGES = {
  fr: {
    panel_header: 'MODÈLE DE PERSONNALISATION',
    panel_sub_header: 'Personnalisez et prévisualisez en temps réel',
    theming_header: 'PERSONNALISATION DU THÈME', // Nouveau titre
    theme_header: 'THÈME',
    theme_label: 'Thèmes',
    style_label: 'Style (Mode)',
    style_switch_light: 'Clair',
    style_switch_dark: 'Sombre',
    // Suppression ou commentaire des autres traductions pour ne garder que la partie THEMING
    /*
    layout_header: 'DISPOSITION',
    layout_label: 'Mise en page (Menu)',
    layout_static: 'Statique',
    layout_offcanvas: 'Hors toile',
    layout_fixed: 'Fixé',
    layout_fixed_offcanvas: 'Fixe hors toile',
    layout_flipped_label: 'Menu inversé',
    layout_dd_open_label: 'Liste déroulante au survol',
    layout_navbar_label: 'Barre de navigation fixe',
    layout_footer_label: 'Pied de page fixe',
    misc_header: 'DIVERS',
    rtl_label: 'Sens RTL'
    */
  }
};
// Themes
TemplateCustomizer.THEMES = [
  {
    name: 'theme-default',
    title: 'Default'
  },
  {
    name: 'theme-semi-dark',
    title: 'Semi Dark'
  },
  {
    name: 'theme-bordered',
    title: 'Bordered'
  }
]

// Theme setting language
TemplateCustomizer.LANGUAGES = {

  fr: {
    panel_header: 'MODÈLE DE PERSONNALISATION',
    panel_sub_header: 'Personnalisez et prévisualisez en temps réel',
    theming_header: 'THÉMATISATION',
    theme_header: 'THÈME',
    theme_label: 'Thèmes',
    style_label: 'Style (Mode)',
    style_switch_light: 'Léger',
    style_switch_dark: 'Sombre',
    layout_header: 'DISPOSITION',
    layout_label: 'Mise en page (Menu)',
    layout_static: 'Statique',
    layout_offcanvas: 'Hors toile',
    layout_fixed: 'Fixé',
    layout_fixed_offcanvas: 'Fixe hors toile',
    layout_flipped_label: 'Menu inversé',
    layout_dd_open_label: 'Liste déroulante au survol',
    layout_navbar_label: 'Barre de navigation fixe',
    layout_footer_label: 'Pied de page fixe',
    misc_header: 'DIVERS',
    rtl_label: 'Sens RTL'
  }


}

export { TemplateCustomizer }
