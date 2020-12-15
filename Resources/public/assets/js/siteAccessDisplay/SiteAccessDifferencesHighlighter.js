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

    window.addEventListener("load", () => {
      if (this.utility.getStateFromUrl("highlight")) {
        setTimeout(() => {
          this.differenceHighlightButton.click();
        });
      }
    });
  }

  highlightDifferencesAndSimilarities() {
    this.differenceHighlightButton.style.animation =
      "opening_subtree 2s ease infinite";

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

      const onlyFirstListKeys = this.filterKeysAccrossLists(firstListKeys, [
        ...secondListKeys,
      ]);

      results.push(...onlyFirstListKeys);

      return results;
    }
  }

  filterKeysAccrossLists(keyList, listOfPotentialTwinKeys) {
    const results = [];
    if (keyList && keyList.length > 0 && listOfPotentialTwinKeys) {
      for (const key of keyList) {
        const potentialTwinKeys = listOfPotentialTwinKeys.filter(
          (potentialTwinKey) =>
            potentialTwinKey.getAttribute("key") === key.getAttribute("key")
        );

        const confirmedTwin = this.utility.findCounterpartNode(
          key,
          potentialTwinKeys
        );

        if (
          !potentialTwinKeys ||
          potentialTwinKeys.length === 0 ||
          !confirmedTwin
        ) {
          results.push(key);
        } else if (confirmedTwin) {
          listOfPotentialTwinKeys.splice(
            listOfPotentialTwinKeys.indexOf(confirmedTwin),
            1
          );
        }
      }
    }

    results.push(...listOfPotentialTwinKeys);

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
        [...secondListValues]
      );

      results.push(...onlyFirstListValues);

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

        const potentialTwinValues = listOfPotentialTwinValues.filter(
          (potentialTwinValue) =>
            potentialTwinValue.getAttribute("value") ===
            value.getAttribute("value")
        );

        if (!potentialTwinValues || potentialTwinValues.length === 0) {
          results.push(value);
          continue;
        }

        let counterpartExists = false;
        for (const potentialValue of potentialTwinValues) {
          if (
            this.findCounterPartValue(
              potentialValue,
              valueKeyParent,
              value.getAttribute("value")
            )
          ) {
            counterpartExists = true;
            listOfPotentialTwinValues.splice(
              listOfPotentialTwinValues.indexOf(potentialValue),
              1
            );
            break;
          }
        }

        if (!counterpartExists) {
          results.push(value);
        }
      }
    }

    results.push(...listOfPotentialTwinValues);

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
    this.differenceHighlightButton.style.animation =
      "opening_subtree 2s ease infinite";

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
    this.differenceHighlightButton.style.animation = "";

    if (hasHighlighted) {
      this.differenceHighlightButton.onclick = (event) => {
        event.stopPropagation();
        this.utility.removeStateFromUrl("highlight");

        // this.utility.createSVGElement(null, "spinner", false);
        this.removeHighlighting();
        this.differenceHighlightButton.style.backgroundColor = "";
        this.flipListener(false);
      };
    } else {
      this.differenceHighlightButton.onclick = (event) => {
        event.stopPropagation();
        this.utility.storeStateInUrl("highlight");

        this.highlightDifferencesAndSimilarities();
        this.differenceHighlightButton.style.backgroundColor = "#0c5472";
        this.flipListener();
      };
    }
  }
}
