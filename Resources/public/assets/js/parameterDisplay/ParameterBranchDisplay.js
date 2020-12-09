class ParameterBranchDisplay {
  subTreeButtons;
  globalSubTreeOpenerButton;

  constructor(parameterToFocus) {
    if (parameterToFocus && parameterToFocus.length > 0) {
      this.subTreeButtons = parameterToFocus;
    } else {
      this.subTreeButtons = [];
    }

    this.globalSubTreeOpenerButton = document.querySelector(
      "#global_open_subtree"
    );
  }

  subTreeOpenClickListener() {
    for (const subTreeButton of this.subTreeButtons) {
      subTreeButton.onclick = (event) => {
        event.preventDefault();
        event.stopPropagation();

        this.openSubTree(subTreeButton.parentElement.parentElement);
      };
    }
  }

  globalSubTreeOpenListener() {
    if (this.globalSubTreeOpenerButton) {
      this.globalSubTreeOpenerButton.onclick = (event) => {
        event.preventDefault();
        event.stopPropagation();

        this.openUpTheEntiretyOfTheSubtrees();
      };
    }
  }

  //---------------------------------------------------------------------------------------------------------------
  // Subtree-Open
  //---------------------------------------------------------------------------------------------------------------

  openSubTree(nodeToFocus) {
    if (nodeToFocus.querySelector(".param_list_items:not(.dont_display)")) {
      let clickEvent = new Event("click");
      nodeToFocus.dispatchEvent(clickEvent);
    } else {
      this.displayEntireBranch(nodeToFocus);
    }
  }

  displayEntireBranch(nodeToFocus) {
    if (nodeToFocus) {
      let childNodes = nodeToFocus.querySelectorAll(".param_list_items");
      childNodes = childNodes ? Array.from(childNodes) : [];
      childNodes.push(nodeToFocus);
      // nodeToFocus.style.animation = "opening_subtree 2s ease infinite";

      this.asynchronouslyDisplayEntireBranch(childNodes);
    }
  }

  asynchronouslyDisplayEntireBranch(nodeList) {
    if (nodeList && nodeList.length > 0) {
      const concurrentNodes =
        nodeList.length > 40
          ? nodeList.splice(0, 40)
          : nodeList.splice(0, nodeList.length);

      for (const node of concurrentNodes) {
        let event = new Event("click");
        node.dispatchEvent(event);
      }

      if (nodeList.length > 0) {
        setTimeout(() => {
          this.asynchronouslyDisplayEntireBranch(nodeList);
        });
      }
      // else {
      //   concurrentNodes[concurrentNodes.length - 1].style.animation = "";
      // }
    }
  }

  //---------------------------------------------------------------------------------------------------------------
  // Global Subtree-Open
  //---------------------------------------------------------------------------------------------------------------

  openUpTheEntiretyOfTheSubtrees() {
    let upperNodes;

    if (document.querySelector(".first_list")) {
      upperNodes = document.querySelectorAll(".first_list > .param_list_items");
    } else {
      upperNodes = document.querySelectorAll(".param_list > .param_list_items");
    }

    for (const upperNode of upperNodes) {
      this.openSubTree(upperNode);
    }
  }
}
