class SearchBarUtility {
  mainSection;
  searchField;
  timeout;

  constructor() {
    this.mainSection = document.querySelector(".cjw_main_section");
    this.searchField = document.querySelector("#cjw_searchbar");
  }

  setUpSearchBar() {
    if (this.mainSection) {
      this.timeout = null;

      this.searchField.addEventListener(
        "input",
        this.controlInputEvent.bind(this)
      );
      this.searchField.addEventListener(
        "keydown",
        this.handleKeyEvent.bind(this)
      );
      this.searchField.addEventListener("keyup", () => {
        this.searchField.classList.remove("keyEventHandled");
      });
    }
  }

  async reactToSearchInput(originalQueryText, searchMode) {
    const queryText = originalQueryText.trim();

    if (queryText.length === 0) {
      await this.resetList();
      return;
    }

    if (
      searchMode === "key" &&
      (new RegExp(/^[.:]/).test(queryText) ||
        new RegExp(/[.:]$/).test(queryText))
    ) {
      return;
    }

    if (this.mainSection) {
      this.removeNodeHighlightings();

      let searchText = queryText;
      let searchPool = this.mainSection;

      if (searchMode === "key") {
        const keys = queryText.split(/[.:]/);

        if (keys && keys.length > 1) {
          searchText = keys.splice(keys.length - 1, 1)[0];
          searchPool = this.lookForKeyHierachie(keys);
        }
      }

      await this.removeRemainingIrrelevantResults(searchText, searchMode);

      const possibleResults = this.conductSearch(
        searchPool,
        searchText,
        searchMode
      );

      if (possibleResults && possibleResults.length > 0) {
        possibleResults[0].scrollIntoView();
      }

      await this.createNodeListToRootAsynchronously(0, possibleResults);
    }
  }

  removeNodeHighlightings() {
    const highlightedNodes = document.querySelectorAll(".first_search_result");

    for (const highlightedNode of highlightedNodes) {
      highlightedNode.classList.remove("first_search_result");
    }
  }

  lookForKeyHierachie(keys) {
    let results = [];

    for (const key of keys) {
      let temporaryResults = [];

      for (const result of results) {
        const temporaryCarrier = result.querySelectorAll(
          `[key="${key.trim()}" i]`
        );

        if (temporaryCarrier && temporaryCarrier.length > 0) {
          temporaryResults.push(...temporaryCarrier);
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

      results = [];

      for (const foundKey of temporaryResults) {
        const nextSearchNode =
          foundKey.parentElement && foundKey.parentElement.children
            ? Array.from(foundKey.parentElement.children).filter((node) =>
                node.classList.contains("param_list_items")
              )
            : null;

        if (nextSearchNode) {
          results.push(...nextSearchNode);
        }
      }
    }

    return results;
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
          result.classList.add("first_search_result");
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

    const lastResults = document.querySelectorAll(".first_search_result");

    for (const lastResult of lastResults) {
      lastResult.classList.remove("first_search_result");
    }
  }

  controlInputEvent(event) {
    event.preventDefault();
    const searchMode = this.searchField.classList.contains("cjw_key_search")
      ? "key"
      : "value";

    clearTimeout(this.timeout);

    this.timeout = setTimeout(() => {
      this.searchField.disabled = true;
      this.reactToSearchInput(event.target.value, searchMode).then(() => {
        this.searchField.disabled = false;
      });
    }, 750);
  }

  handleKeyEvent(event) {
    if (event.keyCode === 13) {
      event.preventDefault();
      event.stopPropagation();
    } else if (
      event.keyCode === 77 &&
      event.altKey &&
      !this.searchField.classList.contains("keyEventHandled")
    ) {
      if (this.searchField.classList.contains("cjw_key_search")) {
        this.searchField.classList.remove("cjw_key_search");
        this.searchField.classList.add("cjw_value_search");
        this.searchField.placeholder = "Search Value...";
      } else {
        this.searchField.classList.remove("cjw_value_search");
        this.searchField.classList.add("cjw_key_search");
        this.searchField.placeholder = "Search Key...";
      }

      this.searchField.classList.add("keyEventHandled");
    }
  }
}
