class SiteAccessDifferencesHighlighter {
  firstList;
  secondList;
  differenceHighlightButton;

  constructor() {
    this.firstList = document.querySelector(".first_list");
    this.secondList = document.querySelector(".second_list");
    this.differenceHighlightButton = document.querySelector(
      "[cjw_id = cjw_highlight_differences]"
    );
  }

  setUpHighlighterButton() {
    this.flipListener(false);
  }

  highlightDifferencesAndSimilarities() {
    if (this.firstList && this.secondList) {
      const uniqueNodes = this.findOutDifferencesBetweenLists();

      this.highlightUniqueNodes(uniqueNodes);
      this.highlightSimilarNodes();
    }
  }

  findOutDifferencesBetweenLists() {
    const results = [];

    const uniqueKeys = this.findOutMissingKeys(
      Array.from(this.firstList.querySelectorAll(".param_list_keys")),
      Array.from(this.secondList.querySelectorAll(".param_list_keys"))
    );

    const uniqueValues = this.findOutDifferentValues(
      Array.from(this.firstList.querySelectorAll(".param_list_values")),
      Array.from(this.secondList.querySelectorAll(".param_list_values"))
    );

    results.push(...uniqueKeys, ...uniqueValues);

    return results;
  }

  findOutMissingKeys(firstListKeys, secondListKeys) {
    if (
      firstListKeys &&
      secondListKeys &&
      firstListKeys.length + secondListKeys.length > 0
    ) {
      const results = [];

      const onlyFirstListKeys = firstListKeys.filter((key) => {
        const actualKey = key.getAttribute("key");

        const counterpartKey = secondListKeys.find(
          (key) => key.getAttribute("key") === actualKey
        );

        return !counterpartKey;
      });

      const onlySecondListKeys = secondListKeys.filter((key) => {
        const actualKey = key.getAttribute("key");

        const counterpartKey = firstListKeys.find(
          (key) => key.getAttribute("key") === actualKey
        );

        return !counterpartKey;
      });

      results.push(...onlyFirstListKeys, ...onlySecondListKeys);

      return results;
    }
  }

  findOutDifferentValues(firstListValues, secondListValues) {
    if (
      firstListValues &&
      secondListValues &&
      firstListValues.length + secondListValues.length > 0
    ) {
      const results = [];

      const onlyFirstListValues = firstListValues.filter((value) => {
        const counterPartValue = this.filterValuesAcrossLists(
          value,
          secondListValues
        );

        // does counterPartValue equate to "true", meaning an object has been found, or not
        return !!counterPartValue;
      });

      const onlySecondListValues = secondListValues.filter((value) => {
        const counterPartValue = this.filterValuesAcrossLists(
          value,
          firstListValues
        );

        // does counterPartValue equate to "true", meaning an object has been found, or not
        return !!counterPartValue;
      });

      results.push(...onlyFirstListValues, ...onlySecondListValues);

      return results;
    }
  }

  filterValuesAcrossLists(value, compareValueList) {
    if (value && compareValueList) {
      const correspondingKey = value.parentElement?.children[0]?.getAttribute(
        "key"
      );

      const actualValue = value.getAttribute("value");

      const counterPartValue = compareValueList.find((node) => {
        this.findCounterPartValue(node, correspondingKey, actualValue);
      });

      // does counterPartValue equate to "true", meaning an object has been found, or not
      return !!counterPartValue;
    }

    return false;
  }

  findCounterPartValue(node, comparisonKey, comparisonValue) {
    if (node) {
      const ownKey = node.parentElement?.children[0]?.getAttribute("key");

      const ownActualValue = node.getAttribute("value");

      return ownKey === comparisonKey && ownActualValue === comparisonValue;
    }

    return false;
  }

  highlightUniqueNodes(uniqueNodeList) {
    if (uniqueNodeList) {
      for (const uniqueNode of uniqueNodeList) {
        uniqueNode.classList.add("difference");
      }
    }
  }

  highlightSimilarNodes() {
    const similarNodesInFirstList = this.firstList.querySelectorAll(
      "div:not(.difference)"
    );
    const similarNodesInSecondList = this.secondList.querySelectorAll(
      "div:not(.difference)"
    );

    const results = [];

    if (similarNodesInFirstList) {
      results.push(...similarNodesInFirstList, ...similarNodesInSecondList);
    }

    for (const similarNode of results) {
      similarNode.classList.add("similarity");
    }
  }

  removeHighlighting() {
    const highlightedSimilarNodes = document.querySelectorAll(".similarity");
    const highlightedUniqueNodes = document.querySelectorAll(".difference");

    for (const highlightedNode of highlightedSimilarNodes) {
      highlightedNode.classList.remove("similarity");
    }

    for (const highlightedNode of highlightedUniqueNodes) {
      highlightedNode.classList.remove("difference");
    }
  }

  flipListener(hasHighlighted = true) {
    if (hasHighlighted) {
      this.differenceHighlightButton.onclick = (event) => {
        event.stopPropagation();

        this.removeHighlighting();
        this.flipListener(false);
      };
    } else {
      this.differenceHighlightButton.onclick = (event) => {
        event.stopPropagation();

        this.highlightDifferencesAndSimilarities();
        this.flipListener();
      };
    }
  }
}
