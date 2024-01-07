import { Controller } from "@hotwired/stimulus"
import TomSelect from "tom-select"

// The following comment enables the lazy loading of the controller:
// @see https://symfony.com/bundles/StimulusBundle/current/index.html#lazy-stimulus-controllers
/* stimulusFetch: 'lazy' */
export default class extends Controller {
  // @see https://stimulus.hotwired.dev/reference/values
  static values = {
    url: String
  }

  connect() {
    "use strict"

    const parent = this

    new TomSelect(this.element, {
      valueField: 'egaid',
      labelField: 'title',
      searchField: [],
      create: false,
      maxOptions: 7,
      hideSelected: true,

      // Minimum query length
      shouldLoad: function (query) {
        return query.length >= 2
      },

      // Fetch remote data
      load: function (query, callback) {
        const url = parent.urlValue + '?q=' + encodeURIComponent(query)

        fetch(url)
          .then(response => response.json())
          .then(json => {
            this.clearOptions()

            callback(json.hits)
          }).catch(() => {
            callback()
          })
      },
      plugins: ['dropdown_input'],
    })
  }
}
