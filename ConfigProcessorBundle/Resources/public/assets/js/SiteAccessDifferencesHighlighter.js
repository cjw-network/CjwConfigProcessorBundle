class SiteAccessDifferencesHighlighter {
  firstList;
  secondList;
  differenceHighlightButton;
  utility;

  constructor() {
    this.firstList = document.querySelector(".first_list");
    this.secondList = document.querySelector(".second_list");
    this.differenceHighlightButton = document.querySelector(
      "[cjw_id = cjw_highlight_differences]"
    );
    this.utility = new Utility();
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
      Array.from(
        this.firstList.querySelectorAll(
          ".param_list_keys:not(.syncScrollAddition)"
        )
      ),
      Array.from(
        this.secondList.querySelectorAll(
          ".param_list_keys:not(.syncScrollAddition)"
        )
      )
    );

    const uniqueValues = this.findOutDifferentValues(
      Array.from(
        this.firstList.querySelectorAll(
          ".param_list_values:not(.syncScrollAddition)"
        )
      ),
      Array.from(
        this.secondList.querySelectorAll(
          ".param_list_values:not(.syncScrollAddition)"
        )
      )
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

      const onlyFirstListKeys = this.filterKeysAccrossLists(
        firstListKeys,
        this.secondList
      );

      const onlySecondListKeys = this.filterKeysAccrossLists(
        secondListKeys,
        this.firstList
      );

      results.push(...onlyFirstListKeys, ...onlySecondListKeys);

      return results;
    }
  }

  filterKeysAccrossLists(keyList, listOfPotentialTwinKeys) {
    const results = [];
    if (keyList && keyList.length > 0 && listOfPotentialTwinKeys) {
      for (const key of keyList) {
        const potentialTwinKeys = listOfPotentialTwinKeys.querySelectorAll(
          `[key="${key.getAttribute("key")}"]:not(.syncScrollAddition)`
        );

        if (
          !potentialTwinKeys ||
          potentialTwinKeys.length === 0 ||
          !this.utility.findCounterpartNode(key, potentialTwinKeys)
        ) {
          results.push(key);
        }
      }
    }

    return results;
  }

  findOutDifferentValues(firstListValues, secondListValues) {
    if (
      firstListValues &&
      secondListValues &&
      firstListValues.length + secondListValues.length > 0
    ) {
      const results = [];

      const onlyFirstListValues = this.filterValuesAcrossLists(
        firstListValues,
        this.secondList
      );

      const onlySecondListValues = this.filterValuesAcrossLists(
        secondListValues,
        this.firstList
      );

      results.push(...onlyFirstListValues, ...onlySecondListValues);

      return results;
    }
  }

  filterValuesAcrossLists(valueList, listOfPotentialTwinValues) {
    const results = [];
    if (valueList && valueList.length > 0 && listOfPotentialTwinValues) {
      for (const value of valueList) {
        let valueKeyParent;

        if (value.classList.contains("inline_value")) {
          valueKeyParent = value.parentElement;
        } else {
          valueKeyParent = value.parentElement.children[0];
        }

        const potentialTwinValues = listOfPotentialTwinValues.querySelectorAll(
          `[value='${value.getAttribute("value")}']:not(.syncScrollAddition)`
        );

        if (!potentialTwinValues || potentialTwinValues.length === 0) {
          results.push(value);
        }

        for (const potentialValue of potentialTwinValues) {
          if (
            this.findCounterPartValue(
              potentialValue,
              valueKeyParent,
              value.getAttribute("value")
            )
          ) {
            break;
          }
        }
      }
    }

    return results;
  }

  findCounterPartValue(node, comparisonKey, comparisonValue) {
    if (node) {
      let ownKey;
      if (node.classList.contains("inline_value")) {
        ownKey = node.parentElement;
      } else {
        ownKey = node.parentElement.children[0];
      }
      // .getAttribute("key");

      const ownActualValue = node.getAttribute("value");

      return (
        !node.classList.contains("syncScrollAddition") &&
        ownActualValue === comparisonValue &&
        this.utility.findCounterpartNode(comparisonKey, [ownKey])
      );
    }

    return false;
  }

  highlightUniqueNodes(uniqueNodeList) {
    if (uniqueNodeList) {
      for (const uniqueNode of uniqueNodeList) {
        uniqueNode.classList.add("addition");

        this.highlightParentKeys(uniqueNode);
      }
    }
  }

  highlightParentKeys(uniqueNode) {
    if (uniqueNode) {
      const uniqueParent = uniqueNode.parentElement;

      if (uniqueParent.parentElement) {
        let upperKey = uniqueParent.parentElement.children[0];

        if (uniqueNode.classList.contains("inline_value")) {
          upperKey = uniqueParent;
        } else if (uniqueNode.classList.contains("param_list_values")) {
          upperKey = uniqueParent.children[0];
        }

        if (
          !upperKey.classList.contains("param_list_items") &&
          !upperKey.classList.contains("addition") &&
          !upperKey.classList.contains("difference")
        ) {
          upperKey.classList.add("difference");
          if (!upperKey.classList.contains("top_nodes")) {
            this.highlightParentKeys(upperKey);
          }
        }
      }
    }
  }

  highlightSimilarNodes() {
    const similarNodesInFirstList = this.firstList.querySelectorAll(
      "div:not(.difference):not(.addition):not(.syncScrollAddition)"
    );
    const similarNodesInSecondList = this.secondList.querySelectorAll(
      "div:not(.difference):not(.addition):not(.syncScrollAddition)"
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
    const highlightedUniqueNodes = document.querySelectorAll(
      ".difference, .addition"
    );

    for (const highlightedNode of highlightedSimilarNodes) {
      highlightedNode.classList.remove("similarity");
    }

    for (const highlightedNode of highlightedUniqueNodes) {
      highlightedNode.classList.remove("difference");
      highlightedNode.classList.remove("addition");
    }
  }

  flipListener(hasHighlighted = true) {
    if (hasHighlighted) {
      this.differenceHighlightButton.onclick = (event) => {
        event.stopPropagation();

        this.removeHighlighting();
        this.differenceHighlightButton.style.backgroundColor = "";
        this.flipListener(false);
      };
    } else {
      this.differenceHighlightButton.onclick = (event) => {
        event.stopPropagation();

        this.highlightDifferencesAndSimilarities();
        this.differenceHighlightButton.style.backgroundColor = "#0c5472";
        this.flipListener();
      };
    }
  }
}
