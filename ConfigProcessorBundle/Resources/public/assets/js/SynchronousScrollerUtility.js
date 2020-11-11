class SynchronousScrollerUtility {
  syncScrollButton;
  comparisonViewFirstList;
  comparisonViewSecondList;

  constructor() {
    this.syncScrollButton = document.querySelector(
      "[cjw_id=cjw_synchronous_scrolling]"
    );

    this.comparisonViewFirstList = document.querySelector(".first_list");
    this.comparisonViewSecondList = document.querySelector(".second_list");
  }

  setUpSynchronousScrollButton() {
    if (this.syncScrollButton) {
      this.syncScrollButton.onclick = (event) => {
        event.preventDefault();
        event.stopPropagation();

        if (this.syncScrollButton.getAttribute("syncScroll") === "active") {
          this.syncScrollButton.setAttribute("syncScroll", "disabled");
          this.syncScrollButton.style.backgroundColor = "";
          this.removeShadowNodes();
        } else {
          this.syncScrollButton.setAttribute("syncScroll", "active");
          this.syncScrollButton.style.backgroundColor = "#0c5472";
          this.prepareListsForSyncScrolling();
        }
      };
    }
  }

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
  }

  removeShadowNodes() {
    const shadowNodes = document.querySelectorAll(
      ".param_list_items .syncScrollAddition"
    );

    for (const shadowNode of shadowNodes) {
      const parent = shadowNode.parentElement;
      parent.removeChild(shadowNode);
    }
  }

  goThroughKeyNodeLists(listToBeCompared, listToCompareTo) {
    if (listToBeCompared && listToCompareTo) {
      const toBeComparedArray = Array.from(listToBeCompared);
      const compareToArray = Array.from(listToCompareTo);

      for (let i = 0; i < toBeComparedArray.length; ++i) {
        const firstKey = toBeComparedArray[i].getAttribute("key");
        let secondKey = null;
        try {
          secondKey = compareToArray[i].getAttribute("key");
        } catch (error) {
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
            if (previousIndex <= 0) {
              compareToArray.splice(
                i,
                0,
                this.addInNodeStructure(
                  toBeComparedArray[i].parentElement,
                  null,
                  compareToArray[0].parentElement
                )
              );
            } else {
              compareToArray.splice(
                i,
                0,
                this.addInNodeStructure(
                  toBeComparedArray[i].parentElement,
                  compareToArray[i - 1].parentElement
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
            this.addInNodeStructure(
              firstValueList[i].parentElement,
              secondValueList[i - 1].parentElement
            )
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

            if (previousIndex <= 0) {
              secondValueList.splice(
                i,
                0,
                this.addInNodeStructure(
                  firstValueList[i].parentElement,
                  null,
                  secondValueList[0].parentElement
                )
              );
            } else {
              secondValueList.splice(
                i,
                0,
                this.addInNodeStructure(
                  firstValueList[i].parentElement,
                  secondValueList[i - 1].parentElement
                )
              );
            }
          }
        }
      }
    }
  }

  goThroughChildrenOfContainer(firstList, secondList) {
    for (let i = 0; i < firstList.length; ++i) {
      const keyList = this.getDirectKeyChildrenOfContainersDirectChildren(
        firstList[i]
      );
      const secondKeyList = this.getDirectKeyChildrenOfContainersDirectChildren(
        secondList[i]
      );

      const firstValueList = this.getValueChildrenOfContainersDirectChildren(
        firstList[i]
      );
      const secondValueList = this.getValueChildrenOfContainersDirectChildren(
        secondList[i]
      );

      if (keyList.length > 0 || secondKeyList.length > 0) {
        if (secondKeyList.length === 0) {
          this.addInMultipleKeyNodesIntoList(
            keyList,
            secondList[0].children[0]
          );
        } else {
          this.goThroughKeyNodeLists(keyList, secondKeyList);
        }

        if (keyList.length === 0) {
          this.addInMultipleKeyNodesIntoList(
            secondKeyList,
            firstList[0].children[0]
          );
        } else {
          this.goThroughKeyNodeLists(secondKeyList, keyList);
        }
      } else {
        // if (firstValueList.length > 0 || secondValueList.length > 0) {
        this.goThroughValuesOfNodeLists(firstValueList, secondValueList);
        this.goThroughValuesOfNodeLists(secondValueList, firstValueList);
        // }
      }
    }
  }

  indexOfKeyInOtherList(key, compareList) {
    if (compareList && compareList.length > 0) {
      const result = compareList.findIndex((compareKey) => {
        return compareKey ? compareKey.getAttribute("key") === key : false;
      });

      return result ?? -1;
    }

    return -1;
  }

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

  addInNodeStructure(
    nodeToAdd,
    nodeAfterWhichToAdd = null,
    givenListToAddTo = null
  ) {
    let listToAddTo = givenListToAddTo;

    if (nodeAfterWhichToAdd && nodeAfterWhichToAdd.parentElement) {
      listToAddTo = nodeAfterWhichToAdd.parentElement;
    } else if (!givenListToAddTo) {
      return;
    }

    const dupShadowNode = nodeToAdd.cloneNode(true);
    dupShadowNode.classList.add("syncScrollAddition");

    const subNodes = dupShadowNode.querySelectorAll("div, span");

    for (const node of subNodes) {
      node.classList.add("syncScrollAddition");
    }

    const subTreeAndLocationButtons = dupShadowNode.querySelectorAll(
      ".open_subtree, .location_info"
    );
    for (const button of subTreeAndLocationButtons) {
      button.parentElement.removeChild(button);
    }

    if (!nodeAfterWhichToAdd) {
      if (listToAddTo.children) {
        const firstNodeOfList = listToAddTo.children[0];
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

    return dupShadowNode.children[0];
  }

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
            this.addInNodeStructure(keyParent, nodeAfterWhichToAdd);
          } else {
            this.addInNodeStructure(keyParent, null, listToBeAddedTo);
          }
        }
      }
    }
  }

  getDirectKeyChildrenOfContainersDirectChildren(containerNode) {
    if (containerNode) {
      const result = [];

      if (!containerNode.children || containerNode.children.length === 0) {
        return result;
      }

      for (const child of containerNode.children) {
        if (child.classList.contains("param_list_items")) {
          if (
            child.children[0] &&
            child.children[0].classList.contains("param_list_keys")
          ) {
            result.push(child.children[0]);
          }
        }
      }

      return result;
    }
  }

  getValueChildrenOfContainersDirectChildren(containerNode) {
    if (containerNode) {
      const result = [];

      if (!containerNode.children || containerNode.children.length === 0) {
        return result;
      }

      for (const child of containerNode.children) {
        if (child.classList.contains("param_list_items")) {
          for (const grandChild of child.children) {
            if (grandChild.classList.contains("param_list_values")) {
              result.push(grandChild);
            }
          }
        }
      }

      return result;
    }
  }
}
