class SynchronousScrollerUtility {
  /** This is the button on the page, which is responsible for toggling the behaviour on and off. */
  syncScrollButton;
  /** Simply the container for the first list of site access variables. */
  comparisonViewFirstList;
  /** Simply the container for the second list of site access variables. */
  comparisonViewSecondList;
  /**
   * Instance of the utility class for access to certain functions in that class.
   * @see Utility
   */
  utility;

  constructor() {
    this.syncScrollButton = document.querySelector(
      "[cjw_id=cjw_synchronous_scrolling]"
    );

    this.comparisonViewFirstList = document.querySelector(".first_list");
    this.comparisonViewSecondList = document.querySelector(".second_list");

    this.utility = new Utility();
  }

  /**
   * This function serves as the entry point for the utility as it is responsible for adding the desired
   * functionality to the toggle-button in order to set off the rest of the function when the behaviour is triggered.
   */
  setUpSynchronousScrollButton() {
    if (this.syncScrollButton) {
      this.syncScrollButton.onclick = (event) => {
        // Just to prevent any unwanted side-effects
        event.preventDefault();
        event.stopPropagation();

        this.syncScrollButton.style.animation =
          "opening_subtree 2s ease infinite";

        // If the behaviour is already active, the behaviour is toggled off and the effects of the function are reverted
        if (this.syncScrollButton.getAttribute("syncScroll") === "active") {
          this.syncScrollButton.setAttribute("syncScroll", "disabled");
          this.utility.removeStateFromUrl("syncScroll");
          this.syncScrollButton.style.backgroundColor = "";
          this.removeShadowNodes();
        } else {
          this.syncScrollButton.setAttribute("syncScroll", "active");
          this.utility.storeStateInUrl("syncScroll");
          // Just make sure, that the there is some visual feedback on the button to signal the behaviour is active
          this.syncScrollButton.style.backgroundColor = "#0c5472";
          this.prepareListsForSyncScrolling();
        }

        this.syncScrollButton.style.animation = "";
      };
    }

    window.addEventListener("load", () => {
      if (this.utility.getStateFromUrl("syncScroll")) {
        setTimeout(() => {
          this.syncScrollButton.click();
        });
      }
    });
  }

  /**
   * Practically the main function of the entire Utility. This function goes through the entire list
   * of keys and sets off the rest of the processes to activate the synchronous scrolling behaviour.
   */
  prepareListsForSyncScrolling() {
    /** Go through the top nodes first and determine the missing pieces right there */
    let firstList = document.querySelectorAll(
      ".first_list > .param_list_items > .top_nodes"
    );
    let secondList = document.querySelectorAll(
      ".second_list > .param_list_items > .top_nodes"
    );

    this.goThroughKeyNodeLists(
      firstList,
      secondList,
      this.comparisonViewSecondList
    );
    this.goThroughKeyNodeLists(
      secondList,
      firstList,
      this.comparisonViewFirstList
    );

    /** Then go and handle all the sub-nodes of the top nodes */
    firstList = document.querySelectorAll(".first_list > .param_list_items");
    firstList = Array.from(firstList);
    secondList = document.querySelectorAll(".second_list > .param_list_items");
    secondList = Array.from(secondList);

    this.goThroughChildrenOfContainer(firstList, secondList);

    this.provideAppropriateOnclicksForAddedNodes();
  }

  /**
   * Function to remove every artifact created by the behaviour. It basically serves as a cleanup
   * function for when the mode is turned off.
   */
  removeShadowNodes() {
    const shadowNodes = document.querySelectorAll(
      ".param_list_items .syncScrollAddition"
    );

    for (const shadowNode of shadowNodes) {
      shadowNode.parentElement.removeChild(shadowNode);
    }
  }

  /**
   * This function takes two lists and goes through every key and sub-key as well
   * as their values in order to determine, where nodes are missing and add the missing nodes in.
   *
   * @param {Array<HTMLElement>} firstList A param_list_items-container which contains a value or a key or both.
   * @param {Array<HTMLElement>} secondList A second param_list_items-container which contains a value or a key or both.
   */
  goThroughChildrenOfContainer(firstList, secondList) {
    for (let i = 0; i < firstList.length; ++i) {
      let keyList = this.utility.getDirectKeyChildrenOfContainersDirectChildren(
        firstList[i]
      );
      let secondKeyList = this.utility.getDirectKeyChildrenOfContainersDirectChildren(
        secondList[i]
      );

      // Is there at least one key in any of the lists
      if (keyList.length > 0 || secondKeyList.length > 0) {
        // If the second list does not contain keys, the first must contain them and therefore, we simply add the missing keys from list one
        if (secondKeyList.length === 0) {
          this.addInMultipleKeyNodesIntoList(
            keyList,
            secondList[i].children[0]
          );
        }
        // If the first list does not contain keys, the second one must and so their nodes are simply added
        else if (keyList.length === 0) {
          this.addInMultipleKeyNodesIntoList(
            secondKeyList,
            firstList[i].children[0]
          );
        }
        // In this instance, both lists contain keys (we don't know which belongs to which, so both have to be checked)
        else {
          this.goThroughKeyNodeLists(keyList, secondKeyList);
          // Since the second list could have been changed during the function before, the list of keys is updated prior to going into the function
          this.goThroughKeyNodeLists(
            this.utility.getDirectKeyChildrenOfContainersDirectChildren(
              secondList[i]
            ),
            keyList
          );
        }
      }
      // If there are no keys in either list, there are either only values or there is nothing left at all (a dead end)
      // There cannot be a combination of a key and a value in the same container, since these are generally combined to one div
      else {
        const firstValueList = this.utility.getValueChildrenOfContainersDirectChildren(
          firstList[i]
        );
        const secondValueList = this.utility.getValueChildrenOfContainersDirectChildren(
          secondList[i]
        );

        // Are there any values or is it a dead end
        if (firstValueList.length > 0 || secondValueList.length > 0) {
          // Same story as before, if there are no values in the one list, there is no more need for any checks or comparisons
          if (secondValueList.length === 0) {
            this.addInMultipleValuesIntoList(
              firstValueList,
              secondList[i].children[0]
            );
          } else if (firstValueList.length === 0) {
            this.addInMultipleValuesIntoList(
              secondValueList,
              firstList[i].children[0]
            );
          } else {
            this.goThroughValuesOfNodeLists(firstValueList, secondValueList);
            this.goThroughValuesOfNodeLists(
              this.utility.getValueChildrenOfContainersDirectChildren(
                secondList[i]
              ),
              firstValueList
            );
          }
        }
      }
    }
  }

  /**
   * Serves to go through two lists of param_list_keys and checks for missing keys in either list, adding missing nodes
   * as synchronous scroll additions and checking the child nodes and keys of equal nodes from the lists.
   *
   * @param listToBeCompared This is the first list of keys which is gone through.
   * @param listToCompareTo This is the list of keys which is checked against.
   */
  goThroughKeyNodeLists(listToBeCompared, listToCompareTo) {
    if (listToBeCompared && listToCompareTo) {
      // For more functions and operations with the list, ensure both are being transferred into arrays
      const toBeComparedArray = Array.from(listToBeCompared);
      const compareToArray = Array.from(listToCompareTo);

      // The keys of the first list is gone through actively and they are checked against the keys of the second list
      for (let i = 0; i < toBeComparedArray.length; ++i) {
        const firstKey = toBeComparedArray[i].getAttribute("key");
        let secondKey = null;
        try {
          // If there are no keys in at this position in the second list, the list has probably ended and thus only additions have to be made
          secondKey = compareToArray[i].getAttribute("key");
        } catch (error) {
          // Since it is ensured by the functions before this that there is not an empty second list, take the last item of that list
          compareToArray.push(
            this.addInNodeStructure(
              toBeComparedArray[i].parentElement,
              compareToArray[i - 1].parentElement
            )
          );

          continue;
        }

        if (firstKey !== secondKey) {
          const stepsUntilKey = this.indexOfKeyInOtherList(
            firstKey,
            compareToArray
          );

          // if -1 has been returned, signalling that the key is not present in the other list, add in a ghost node structure
          if (stepsUntilKey < 0) {
            const previousIndex = i - 1;
            // If the previous key is smaller than 0, then the node has to be added to the beginning of the list
            if (previousIndex < 0) {
              compareToArray.unshift(
                this.addInNodeStructure(
                  toBeComparedArray[i].parentElement,
                  null,
                  compareToArray[0].parentElement.parentElement
                )
              );
            } else {
              compareToArray.splice(
                i,
                0,
                this.addInNodeStructure(
                  toBeComparedArray[i].parentElement,
                  compareToArray[previousIndex].parentElement
                )
              );
            }
          }
        } else if (toBeComparedArray[i].parentElement.children.length > 1) {
          const firstParentList = [toBeComparedArray[i].parentElement];
          const secondParentList = [compareToArray[i].parentElement];

          this.goThroughChildrenOfContainer(firstParentList, secondParentList);
        }
      }
    }
  }

  /**
   * The value version of goThroughKeyNodeLists. Where lists of values are gone through and missing ones are added to the
   * lists.
   *
   * @param {Array<HTMLElement>} firstValueList The first list of values which is gone through primarily.
   * @param {Array<HTMLElement>} secondValueList The second list of values which are checked against the ones from the first list.
   *
   * @see goThroughKeyNodeLists
   */
  goThroughValuesOfNodeLists(firstValueList, secondValueList) {
    if (firstValueList && secondValueList) {
      if (firstValueList.length === 0 && secondValueList.length === 0) {
        return;
      }

      firstValueList = Array.from(firstValueList);
      secondValueList = Array.from(secondValueList);

      for (let i = 0; i < firstValueList.length; ++i) {
        const firstValue = firstValueList[i].getAttribute("value");
        let secondValue = null;
        try {
          secondValue = secondValueList[i].getAttribute("value");
        } catch (error) {
          secondValueList.push(
            this.addInNodeStructure(firstValueList[i], secondValueList[i - 1])
          );

          continue;
        }

        if (firstValue !== secondValue) {
          const stepsUntilValue = this.indexOfValueInOtherList(
            firstValue,
            secondValueList
          );

          if (stepsUntilValue < 0) {
            const previousIndex = i - 1;

            if (previousIndex < 0) {
              secondValueList.unshift(
                this.addInNodeStructure(
                  firstValueList[i],
                  null,
                  secondValueList[0].parentElement
                )
              );
            } else {
              secondValueList.splice(
                i,
                0,
                this.addInNodeStructure(
                  firstValueList[i],
                  secondValueList[i - 1]
                )
              );
            }
          }
        }
      }
    }
  }

  /**
   * Takes all added nodes of the process and adds an appropriate onclick listener
   * to all of them, to ensure the correct functionality, when synchronously opening the
   * subtrees.
   */
  provideAppropriateOnclicksForAddedNodes() {
    const additionsMade = document.querySelectorAll(
      ".syncScrollAddition:not(.param_list_keys)"
    );
    const parameterDisplay = new ParameterDisplay();

    for (const addition of additionsMade) {
      if (addition.classList.contains("param_list_values")) {
        const firstChildOfParentContainer = addition.parentElement.children[0];

        if (
          firstChildOfParentContainer.classList.contains(
            "param_list_key_without_child"
          )
        ) {
          firstChildOfParentContainer.onclick = "";
          parameterDisplay.setAppropriateOnClick(addition.parentElement);
        }
      }

      parameterDisplay.setAppropriateOnClick(addition);
    }
  }

  /**
   * Takes a given key and list and searches a key with the same "key"-value as the one given.
   * If it is found, the index of the key in the list is given back.
   *
   * @param key The key to be searched for in the given list.
   * @param compareList The array of keys in which to search for the key.
   * @returns {number} Returns a number which represents the index of the key or "-1" if the key couldn't be found.
   */
  indexOfKeyInOtherList(key, compareList) {
    if (compareList && compareList.length > 0) {
      const result = compareList.findIndex((compareKey) => {
        return compareKey ? compareKey.getAttribute("key") === key : false;
      });

      return result ?? -1;
    }

    return -1;
  }

  /**
   * The value counterpart of indexOfKeyInOtherList, which searches for a given value which matches the
   * "value"-value of the given node in the given list.
   *
   * @param {string} value The given value for which to search in the list.
   * @param {array} compareList The list in which the value is to be searched for.
   * @returns {number} Returns the index of the found value in the array or "-1" if no value could be found.
   *
   * @see indexOfKeyInOtherList
   */
  indexOfValueInOtherList(value, compareList) {
    if (compareList && compareList.length > 0) {
      const result = compareList.findIndex((compareValue) => {
        return compareValue
          ? compareValue.getAttribute("value") === value
          : false;
      });

      return result ?? -1;
    }

    return -1;
  }

  /**
   * Adds the duplicate of the given node either into the list of nodes which is given to it or after the node given to the function.
   *
   * @param {HTMLElement} nodeToAdd The html element node to add to the given lists.
   * @param {HTMLElement} nodeAfterWhichToAdd Another html element, after which the nodeToAdd will be added.
   * @param {HTMLElement} givenListToAddTo An html element into which to add the given node.
   * @returns {HTMLElement} Returns a duplicate node to the nodeToAdd from the list the node has been added to.
   */
  addInNodeStructure(
    nodeToAdd,
    nodeAfterWhichToAdd = null,
    givenListToAddTo = null
  ) {
    let listToAddTo = givenListToAddTo;
    listToAddTo = listToAddTo
      ? this.confirmListToAddToOrDeliverNewOne(nodeToAdd, listToAddTo)
      : listToAddTo;

    if (nodeAfterWhichToAdd && nodeAfterWhichToAdd.parentElement) {
      listToAddTo = nodeAfterWhichToAdd.parentElement;
    } else if (!listToAddTo) {
      return null;
    }

    const dupShadowNode = nodeToAdd.cloneNode(true);
    dupShadowNode.classList.add("syncScrollAddition");

    this.cleanUpDuplicatedNode(dupShadowNode);

    if (!nodeAfterWhichToAdd) {
      // Since not every browser supports directly adding in the node in the beginning of the list, the first node is taken as the point to add before.
      const firstNodeOfList = this.getListsFirstNonKeyNode(listToAddTo);
      if (firstNodeOfList) {
        listToAddTo.insertBefore(dupShadowNode, firstNodeOfList);
      } else {
        listToAddTo.appendChild(dupShadowNode);
      }
    } else if (nodeAfterWhichToAdd) {
      const nextSiblingBeforeWhichToAdd =
        nodeAfterWhichToAdd.nextElementSibling;

      if (nextSiblingBeforeWhichToAdd) {
        listToAddTo.insertBefore(dupShadowNode, nextSiblingBeforeWhichToAdd);
      } else {
        listToAddTo.appendChild(dupShadowNode);
      }
    }

    if (dupShadowNode.classList.contains("param_list_values")) {
      return dupShadowNode;
    }
    return dupShadowNode.children[0];
  }

  /**
   * Takes a list of keys which are to be added into the given list-element or after a given node.
   * Even though both values are optional, at least one must be given or otherwise the
   * function will not do anything.
   *
   * @param {array<HTMLElement>} arrayOfKeys An array of key elements which will be added to the given lists.
   * @param {HTMLElement} nodeAfterWhichToAdd An element after which to add the keys given to the function.
   * @param {HTMLElement} listToBeAddedTo An element which serves as a list into which the keys are being added.
   */
  addInMultipleKeyNodesIntoList(
    arrayOfKeys,
    nodeAfterWhichToAdd = null,
    listToBeAddedTo = null
  ) {
    if (arrayOfKeys && (listToBeAddedTo || nodeAfterWhichToAdd)) {
      for (const key of arrayOfKeys) {
        const keyParent = key.parentElement;

        if (keyParent) {
          if (nodeAfterWhichToAdd) {
            nodeAfterWhichToAdd = this.addInNodeStructure(
              keyParent,
              nodeAfterWhichToAdd
            ).parentElement;
          } else {
            nodeAfterWhichToAdd = this.addInNodeStructure(
              keyParent,
              null,
              listToBeAddedTo
            ).parentElement;
          }
        }
      }
    }
  }

  /**
   * The value counter part to the addInMultipleKeyNodesIntoList, which takes a list of values and adds them into
   * a given list or after a given node. Even though both values are optional, at least one must be given or otherwise the
   * function will not do anything.
   *
   * @param {array<HTMLElement>} arrayOfValues An array of value nodes which is going to be added to the given nodes.
   * @param {HTMLElement} nodeAfterWhichToAdd A node after which the given values are going to be added.
   * @param {HTMLElement} listToBeAddedTo An element which serves as the list the values are being added into.
   *
   * @see addInMultipleKeyNodesIntoList
   */
  addInMultipleValuesIntoList(
    arrayOfValues,
    nodeAfterWhichToAdd = null,
    listToBeAddedTo = null
  ) {
    if (arrayOfValues && (listToBeAddedTo || nodeAfterWhichToAdd)) {
      for (const value of arrayOfValues) {
        if (
          value.parentElement.children[0].classList.contains("param_list_keys")
        ) {
          if (nodeAfterWhichToAdd) {
            nodeAfterWhichToAdd = this.addInNodeStructure(
              value,
              nodeAfterWhichToAdd
            );
          } else {
            nodeAfterWhichToAdd = this.addInNodeStructure(
              value,
              null,
              listToBeAddedTo
            );
          }
        }
      }
    }
  }

  /**
   * Helper function which takes a node and clears the node and all of its children from the
   * control elements of the original node-structure.
   *
   * @param {HTMLElement} duplicateNode The node which has been duplicated and is supposed to be cleared.
   */
  cleanUpDuplicatedNode(duplicateNode) {
    if (duplicateNode) {
      duplicateNode.classList.remove("addition");

      const subNodes = duplicateNode.querySelectorAll("div, span");

      for (const node of subNodes) {
        node.classList.add("syncScrollAddition");

        if (node.classList.contains("addition")) {
          node.classList.remove("addition");
        }
      }

      const subTreeAndLocationButtons = duplicateNode.querySelectorAll(
        ".parameter_buttons, .param_item_toggle"
      );
      for (const button of subTreeAndLocationButtons) {
        button.parentElement.removeChild(button);
      }
    }
  }

  /**
   * Checks a given list and node for whether the given list actually resembles the counterpart parent element of the list
   * the node is going to be added in. If that is not the case, the function tries to find the fitting parent element in the
   * direct hierarchy of the given list.
   *
   * @param {HTMLElement} nodeToAdd The node which is going to be added to the given list.
   * @param {HTMLElement} listToBeAddedTo The container node which serves as the list the given node is going to be added into.
   * @returns {null|HTMLElement} Returns the list the node can be added in or null, if no adequate container could be found.
   */
  confirmListToAddToOrDeliverNewOne(nodeToAdd, listToBeAddedTo) {
    if (nodeToAdd && listToBeAddedTo && nodeToAdd.parentElement) {
      if (listToBeAddedTo.classList.contains("param_list_items")) {
        const nodeParent = nodeToAdd.parentElement;
        const keyOfParent = nodeParent.children[0];

        if (!keyOfParent.classList.contains("param_list_keys")) {
          return null;
        }

        const firstKeyOfListToBeAddedTo = listToBeAddedTo.children[0];

        if (
          !firstKeyOfListToBeAddedTo.classList.contains("param_list_keys") ||
          firstKeyOfListToBeAddedTo.getAttribute("key") !==
            keyOfParent.getAttribute("key")
        ) {
          return this.confirmListToAddToOrDeliverNewOne(
            nodeToAdd,
            listToBeAddedTo.parentElement
          );
        } else {
          return listToBeAddedTo;
        }
      } else if (
        listToBeAddedTo.classList.contains("param_list") &&
        nodeToAdd.parentElement.classList.contains("param_list") &&
        listToBeAddedTo !== nodeToAdd.parentElement
      ) {
        return listToBeAddedTo;
      }
    }

    return null;
  }

  /**
   * Finds the first node of a given container node which is not a param_list_key and returns the found node.
   *
   * @param {HTMLElement} listToBeAddedTo The container node in which to search for the first node.
   * @returns {null|HTMLElement} Returns either the found first non-key-node or null if non could be found.
   */
  getListsFirstNonKeyNode(listToBeAddedTo) {
    if (listToBeAddedTo && listToBeAddedTo.children) {
      const firstChild = listToBeAddedTo.children[0];
      if (firstChild.classList.contains("param_list_keys")) {
        let nextChild = firstChild;
        let i = 0;

        while (
          nextChild.classList.contains("param_list_keys") &&
          i + 1 < listToBeAddedTo.children.length
        ) {
          nextChild = listToBeAddedTo.children[++i];
        }

        return nextChild;
      } else {
        return firstChild;
      }
    } else {
      return null;
    }
  }
}
