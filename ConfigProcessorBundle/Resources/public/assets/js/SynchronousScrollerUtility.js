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

  goThroughChildrenOfContainer(firstList, secondList) {
    for (let i = 0; i < firstList.length; ++i) {
      let keyList = this.getDirectKeyChildrenOfContainersDirectChildren(
        firstList[i]
      );
      let secondKeyList = this.getDirectKeyChildrenOfContainersDirectChildren(
        secondList[i]
      );

      if (keyList.length > 0 || secondKeyList.length > 0) {
        if (secondKeyList.length === 0) {
          this.addInMultipleKeyNodesIntoList(
            keyList,
            secondList[i].children[0]
          );
        } else if (keyList.length === 0) {
          this.addInMultipleKeyNodesIntoList(
            secondKeyList,
            firstList[i].children[0]
          );
        } else {
          this.goThroughKeyNodeLists(keyList, secondKeyList);
          this.goThroughKeyNodeLists(
            this.getDirectKeyChildrenOfContainersDirectChildren(secondList[i]),
            keyList
          );
        }
      } else {
        const firstValueList = this.getValueChildrenOfContainersDirectChildren(
          firstList[i]
        );
        const secondValueList = this.getValueChildrenOfContainersDirectChildren(
          secondList[i]
        );

        if (firstValueList.length > 0 || secondValueList.length > 0) {
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
            this.goThroughValuesOfNodeLists(secondValueList, firstValueList);
          }
        }
      }
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

            if (previousIndex < 0) {
              secondValueList.unshift(
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
    listToAddTo = listToAddTo
      ? this.confirmListToAddToOrDeliverNewOne(nodeToAdd, listToAddTo)
      : listToAddTo;

    if (nodeAfterWhichToAdd && nodeAfterWhichToAdd.parentElement) {
      listToAddTo = nodeAfterWhichToAdd.parentElement;
    } else if (!listToAddTo) {
      return;
    }

    const dupShadowNode = nodeToAdd.cloneNode(true);
    dupShadowNode.classList.add("syncScrollAddition");

    this.cleanUpDuplicatedNode(dupShadowNode);

    if (!nodeAfterWhichToAdd) {
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
            this.addInNodeStructure(value, nodeAfterWhichToAdd);
          } else {
            this.addInNodeStructure(value, null, listToBeAddedTo);
          }
        }
      }
    }
  }

  cleanUpDuplicatedNode(duplicateNode) {
    if (duplicateNode) {
      const subNodes = duplicateNode.querySelectorAll("div, span");

      for (const node of subNodes) {
        node.classList.add("syncScrollAddition");
      }

      const subTreeAndLocationButtons = duplicateNode.querySelectorAll(
        ".open_subtree, .location_info, .param_item_toggle"
      );
      for (const button of subTreeAndLocationButtons) {
        button.parentElement.removeChild(button);
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
        } else if (child.classList.contains("param_list_values")) {
          result.push(child);
        }
      }

      return result;
    }
  }

  confirmListToAddToOrDeliverNewOne(nodeToAdd, listToBeAddedTo) {
    if (nodeToAdd && listToBeAddedTo) {
      if (
        nodeToAdd.parentElement &&
        listToBeAddedTo.classList.contains("param_list_items")
      ) {
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
      }
    }

    return null;
  }

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
