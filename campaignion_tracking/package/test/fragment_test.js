/* global suite test setup teardown suiteSetup suiteTeardown */

import { strict as assert } from 'assert'
import sinon from 'sinon'

import {
  parseLocationHash,
  consumeLocationHashForPrefixes,
  parseLocationHashForPrefixes,
  parsePart
} from '../src/fragment'

suite('parse location hash', () => {
  test('empty hash returns empty list', () => {
    const locationHash = ''
    const parsed = parseLocationHash(locationHash)
    sinon.assert.match(parsed, [])
  })

  test('delimit parts with semicolon', () => {
    const locationHash = 'prefix1=a;prefix2=b'
    const parsed = parseLocationHash(locationHash)
    assert(Array.isArray(parsed))
    assert(parsed.length === 2)
  })

  test('return list of match objects', () => {
    const locationHash = 'prefix1=a;prefix2=b,c'
    const parsed = parseLocationHash(locationHash)
    assert(parsed[0].prefix === 'prefix1')
    sinon.assert.match(parsed[0].codes, ['a'])
    assert(parsed[1].prefix === 'prefix2')
    sinon.assert.match(parsed[1].codes, ['b', 'c'])
  })

  test('return nothing if no prefix is whitelisted', () => {
    const locationHash = 'prefix1=a;prefix2=b,c;prefix3:myid=d'
    const parsed = parseLocationHashForPrefixes([], locationHash)
    assert(parsed.length === 0)
  })

  test('return matches for whitelisted prefixes', () => {
    const locationHash = 'prefix1=a;prefix2=b,c;prefix3:myid=d'
    const parsed = parseLocationHashForPrefixes(['prefix1', 'prefix3'], locationHash)
    // matches nested objects
    sinon.assert.match(parsed[0], { prefix: 'prefix1' })
    sinon.assert.match(parsed[1], { prefix: 'prefix3' })
  })

  test('return matches for whitelisted prefixes in order of location hash', () => {
    const locationHash = 'prefix1=a;prefix2=b,c;prefix3:myid=d'
    const parsed = parseLocationHashForPrefixes(['prefix3', 'prefix1'], locationHash)
    // matches nested objects
    sinon.assert.match(parsed[0], { prefix: 'prefix1' })
    sinon.assert.match(parsed[1], { prefix: 'prefix3' })
  })

  test('consume only prefixed parts of a location hash', () => {
    const locationHash = 'prefix1=a;prefix2=b,c;prefix3:myid=d'
    const parsed = consumeLocationHashForPrefixes(['prefix2'], locationHash)
    // matches nested objects
    sinon.assert.match(parsed.items[0], { prefix: 'prefix2' })
  })
})

suite('parse location parts', () => {
  test('return a match object without optional id', () => {
    const part = 'prefix1=a'
    const parsed = parsePart(part)
    sinon.assert.match(parsed, { prefix: 'prefix1', id: '', codes: ['a'] })
  })

  test('return a match object with optional id', () => {
    const part = 'prefix1:myid=a'
    const parsed = parsePart(part)
    sinon.assert.match(parsed, { prefix: 'prefix1', id: 'myid', codes: ['a'] })
  })

  test('a match object includes the original string too', () => {
    const part = 'prefix1:myid=a'
    const parsed = parsePart(part)
    sinon.assert.match(parsed, {
      prefix: 'prefix1',
      id: 'myid',
      codes: ['a'],
      origPart: 'prefix1:myid=a'
    })
  })
})
