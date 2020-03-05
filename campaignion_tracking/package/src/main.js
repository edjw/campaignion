import * as tm from './tracker-manager'
import * as listener from './listener'
import * as gtm from './gtm'
import * as dp from './debug'

/**
 * Check if we want debugging output.
 *
 * Parse the value as int, so we can disable debugging by setting to "0".
 * `sessionStorage` only stores strings.
 */
// eslint-disable-next-line no-unneeded-ternary
var debug = parseInt(sessionStorage.getItem('campaignion_debug')) ? true : false

// some out-of-the-box prefixes
export const codePrefixes = ['t', 'd', 'w']

// ensure window.dataLayer
window.dataLayer = window.dataLayer || []

// common tracker manager, listener, gtm
export const tracker = new tm.TrackerManager(debug)
export const gtmTracker = new gtm.GTMTracker(tracker, window.dataLayer, debug)
export const fragmentListener = new listener.FragmentListener(tracker, codePrefixes, debug)
fragmentListener.setup()
dp.setupDebugProxy('dataLayer')

export const trackingCodes = {
  ds: 'donationSuccess',
  s: 'submission'
}

const printDebug = (...args) => {
  if (debug) {
    console.debug('[campaignion_tracking]', '(drupal)', ...args)
  }
}

export const codeSubscription = tracker.subscribe('code', (e) => {
  printDebug('handle_code', e)

  // map event codes to event names
  const events = e.items.reduce((acc, item) => {
    if (item.prefix === 't') {
      item.codes.forEach((code) => {
        if (trackingCodes[code]) {
          acc.tracking.events.push(trackingCodes[code])
        }
      })
    }
    if (item.prefix === 'w') {
      if (item.id === 'sid') {
        acc.webform.sid = item.codes[0]
      }
    }
    if (item.prefix === 'd') {
      if (item.id === 'm') {
        acc.donation.method = item.codes[0]
      }
    }
    return acc
  }, { tracking: { events: [] }, webform: {}, donation: {} })

  printDebug('handle_events', events)

  const data = { tid: events.webform.sid || null }
  const context = {
    webform: { sid: events.webform.sid || null },
    donation: { paymethod: events.donation.method || 'unknown' }
  }

  if (events.tracking.events.includes('donationSuccess')) {
    tracker.publish('donation', { name: 'donationSuccess', data: data, context: context })
  }
  if (events.tracking.events.includes('submission')) {
    tracker.publish('webform', { name: 'submission', data: data, context: context })
  }
})

// re-exports
export { tm, listener, debug, gtm, dp }
export { fragment } from './fragment'
