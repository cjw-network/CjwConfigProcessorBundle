class SearchBarUtility {
  mainSection;
  searchField;
  modeSwitchButton;
  clearInputButton;
  timeout;
  siteAccessPresent;
  utility;

  constructor() {
    this.mainSection = document.querySelector(".cjw_main_section");
    this.searchField = document.getElementById("cjw_searchbar");
    this.modeSwitchButton = document.getElementById("cjw_searchbar_swap_mode");
    this.clearInputButton = document.getElementById("cjw_searchbar_clear");
    this.siteAccessPresent = !!document.querySelector("[siteaccess]");
    this.utility = new Utility();
  }

  /**
   * Sets up the searchbar. That means it provides the searchbar with the necessary listeners and functions
   * for it to handle its task.
   */
  setUpSearchBar() {
    if (this.mainSection && this.searchField) {
      this.timeout = null;

      // Event listener for the actual text being put into the searchbar
      this.searchField.addEventListener(
        "input",
        this.controlInputEvent.bind(this)
      );

      if (this.modeSwitchButton) {
        //The switch button should do the same as the key combination for the searchbar
        this.modeSwitchButton.addEventListener(
          "click",
          this.handleModeSwitchOnClick.bind(this)
        );
      }

      if (this.clearInputButton) {
        this.clearInputButton.style.opacity = 0;

        this.clearInputButton.addEventListener(
          "click",
          this.clearInput.bind(this)
        );
      }
      //Event listener which handles the search mode being switched and the enter-key event
      this.searchField.addEventListener(
        "keydown",
        this.handleKeyEvent.bind(this)
      );
      // Event listener for the debouncing of the key down event (otherwise the event is triggered too often)
      this.searchField.addEventListener("keyup", () => {
        this.searchField.classList.remove("switchModeHandled");
      });

      window.addEventListener(
        "load",
        this.handleUrlStateAfterDocLoaded.bind(this)
      );
    }
  }

  controlInputEvent(event) {
    event.preventDefault();
    const searchMode = this.searchField.classList.contains("cjw_key_search")
      ? "key"
      : "value";

    if (this.clearInputButton.style.opacity === "0") {
      this.clearInputButton.style.opacity = "";
    }

    clearTimeout(this.timeout);

    this.timeout = setTimeout(() => {
      this.reactToSearchInput(event.target.value, searchMode);
    }, 750);
  }

  handleModeSwitchOnClick(event) {
    event.preventDefault();
    event.stopPropagation();

    this.switchSearchMode();
  }

  handleKeyEvent(event) {
    if (event.keyCode === 13) {
      event.preventDefault();
      event.stopPropagation();
    } else if (
      event.keyCode === 77 &&
      event.altKey &&
      !this.searchField.classList.contains("switchModeHandled")
    ) {
      this.switchSearchMode();
      this.searchField.classList.add("switchModeHandled");
    }
  }

  handleUrlStateAfterDocLoaded() {
    setTimeout(() => {
      const query = this.utility.getStateFromUrl("query");

      if (query) {
        const searchMode = this.utility.getStateFromUrl("qType") ?? "key";

        if (
          (searchMode === "key" &&
            !this.searchField.classList.contains("cjw_key_search")) ||
          (searchMode === "value" &&
            !this.searchField.classList.contains("cjw_value_search"))
        ) {
          this.switchSearchMode();
        }
        this.searchField.value = query;
        this.reactToSearchInput(query, searchMode);
      }
    }, 250);
  }

  switchSearchMode() {
    let newMode;
    if (this.searchField.classList.contains("cjw_key_search")) {
      this.searchField.classList.remove("cjw_key_search");
      this.searchField.classList.add("cjw_value_search");
      this.searchField.placeholder = "Search Value...";
      newMode = "value";
    } else {
      this.searchField.classList.remove("cjw_value_search");
      this.searchField.classList.add("cjw_key_search");
      this.searchField.placeholder = "Search Key...";
      newMode = "key";
    }

    if (this.searchField.value.length > 0) {
      this.reactToSearchInput(this.searchField.value, newMode);
    }
  }

  clearInput(event) {
    event.preventDefault();
    event.stopPropagation();

    this.searchField.value = "";
    this.resetList();
  }

  /**
   * Reacts to a given search text. It takes the text and uses it to search for nodes on the site which fit the
   * search text. It is basically the main trigger for the search.
   *
   * @param {string} originalQueryText The text being put into the search field without any changes by the code.
   * @param {string} searchMode The mode with which to search.
   * @returns {Promise<void>} Returns a promise which can be ignored as it does not provide much.
   */
  async reactToSearchInput(originalQueryText, searchMode = "key") {
    const queryText = originalQueryText.trim();

    // If the search field input has been emptied, empty the list of results too
    if (queryText.length === 0) {
      await this.resetList();
      return;
    }

    // This is responsible for handling the subtree-search, where multiple segments of the key are entered into the search field
    if (
      searchMode === "key" &&
      !this.siteAccessPresent &&
      (new RegExp(/^[.:]/).test(queryText) ||
        new RegExp(/[.:]$/).test(queryText))
    ) {
      // since when the input ends on an "end"-symbol for the subtree-segment, there shouldn't be a display of anything
      return;
    }

    if (this.mainSection) {
      // remove prior search results first
      this.removeNodeHighlightings();

      let searchText = queryText;
      let searchPool = this.mainSection;

      // In the case the user is searching for a key
      if (searchMode === "key" && !this.siteAccessPresent) {
        const keys = queryText.split(/[.:]/);

        if (keys && keys.length > 1) {
          // the last segment of the search text is the one the user actually searches for, so remove that and treat the rest as subtree segments to go through
          searchText = keys.splice(keys.length - 1, 1)[0];
          searchPool = this.lookForKeyHierachie(keys);
        }
      }

      // remove every visible node, which does not fit the search text
      await this.removeRemainingIrrelevantResults(searchText, searchMode);

      const possibleResults = this.conductSearch(
        searchPool,
        searchText,
        searchMode
      );

      // Simply display the first result of the search (if there is one)
      if (possibleResults && possibleResults.length > 0) {
        possibleResults[0].scrollIntoView();
      }

      // build the rest of the search results
      await this.createNodeListToRootAsynchronously(0, possibleResults);
      this.utility.alterStateInUrl("query", queryText);
      this.utility.alterStateInUrl("qType", searchMode);
    }
  }

  removeNodeHighlightings() {
    const highlightedNodes = document.querySelectorAll(".search_result");

    for (const highlightedNode of highlightedNodes) {
      highlightedNode.classList.remove("search_result");
    }
  }

  lookForKeyHierachie(keys) {
    let previousResults = [];

    for (const key of keys) {
      let temporaryResults = [];

      for (const result of previousResults) {
        const temporaryCarrier = result.querySelectorAll(
          `[key="${key.trim()}" i]`
        );

        if (temporaryCarrier && temporaryCarrier.length > 0) {
          // temporaryResults.push(...temporaryCarrier);

          for (const potentialNode of temporaryCarrier) {
            if (potentialNode.parentElement === result) {
              temporaryResults.push(potentialNode);
            }
          }
        }
      }

      if (temporaryResults.length === 0) {
        const temporaryCarrier = document.querySelectorAll(
          `.top_nodes[key="${key.trim()}" i]`
        );

        if (temporaryCarrier && temporaryCarrier.length > 0) {
          temporaryResults.push(...temporaryCarrier);
        }
      }

      previousResults = [];

      for (const foundKey of temporaryResults) {
        const nextSearchNode =
          foundKey.parentElement && foundKey.parentElement.children
            ? Array.from(foundKey.parentElement.children).filter((node) =>
                node.classList.contains("param_list_items")
              )
            : null;

        if (nextSearchNode) {
          previousResults.push(...nextSearchNode);
        }
      }
    }

    return previousResults;
  }

  async removeRemainingIrrelevantResults(searchText, searchMode = "key") {
    let nonRelevantVisibleResults = this.mainSection.querySelectorAll(
      `div:not(.dont_display):not([${searchMode}*="${searchText}" i]), [${searchMode}]`
    );
    nonRelevantVisibleResults = Array.from(
      nonRelevantVisibleResults
    ).filter((node) => node.classList.contains("param_list_items"));

    for (const nonRelevantResult of nonRelevantVisibleResults) {
      nonRelevantResult.classList.add("dont_display");
    }
  }

  conductSearch(searchPool, searchText, searchMode = "key") {
    const possibleResults = [];

    if (searchPool === this.mainSection) {
      const temporaryResultCarrier = searchPool.querySelectorAll(
        `[${searchMode}*="${searchText.trim()}" i]:not(.syncScrollAddition)`
      );

      if (temporaryResultCarrier) {
        possibleResults.push(...temporaryResultCarrier);
      }
    } else {
      for (const pool of searchPool) {
        const temporaryResultCarrier = pool.querySelectorAll(
          `[${searchMode}*="${searchText.trim()}" i]:not(.syncScrollAddition)`
        );

        if (temporaryResultCarrier) {
          possibleResults.push(...temporaryResultCarrier);
        }
      }
    }

    return possibleResults;
  }

  async createNodeListToRootAsynchronously(counter, nodeList) {
    if (nodeList && nodeList.length > counter >= 0) {
      do {
        const result = nodeList[counter];

        if (result) {
          this.createNodeListToRoot(result);
          result.classList.add("search_result");
        }

        ++counter;
      } while (counter < nodeList.length && counter % 40 !== 0);

      if (counter < nodeList.length) {
        await setTimeout(() => {
          this.createNodeListToRootAsynchronously(counter, nodeList);
        });
      }
    }
  }

  createNodeListToRoot(node) {
    if (node.offsetParent === null || node.classList.contains("dont_display")) {
      const nodeParent = node.parentElement;

      if (nodeParent && !nodeParent.classList.contains("param_list")) {
        this.createNodeListToRoot(nodeParent);
      }

      node.classList.remove("dont_display");
    }
  }

  async resetList() {
    this.clearInputButton.style.opacity = 0;
    await this.removeRemainingIrrelevantResults("");
    const rootNodes = document.querySelectorAll(
      ".param_list > .param_list_items"
    );

    for (const node of rootNodes) {
      node.classList.remove("dont_display");

      for (const childNode of node.children) {
        if (childNode.classList.contains("param_list_keys")) {
          childNode.classList.remove("dont_display");
        }
      }
    }

    const lastResults = document.querySelectorAll(".search_result");

    for (const lastResult of lastResults) {
      lastResult.classList.remove("search_result");
    }

    this.utility.removeStateFromUrl("query");
    this.utility.removeStateFromUrl("qType");
  }
}
