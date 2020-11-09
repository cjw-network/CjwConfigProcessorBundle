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
        // this.flipSubtreeAction(subTreeButton, true);
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
    const searchLimiter = nodeToFocus.querySelector(".param_list_keys");

    this.displayEntireBranch(nodeToFocus);

    if (document.querySelector(".second_list")) {
      // let testText = `[key="${searchLimiter.getAttribute("key")}"]`;
      const desiredNodes = document.querySelectorAll(
        `[key="${searchLimiter.getAttribute("key")}"]`
      );

      if (desiredNodes.length > 1) {
        const desiredNode =
          desiredNodes[0].parentElement === nodeToFocus
            ? desiredNodes[1].parentElement
            : desiredNodes[0].parentElement;

        this.displayEntireBranch(desiredNode);
      }
    }
  }

  displayEntireBranch(nodeToFocus) {
    if (nodeToFocus) {
      let childNodes = nodeToFocus.querySelectorAll(".param_list_items");
      childNodes = childNodes ? Array.from(childNodes) : [];
      childNodes.push(nodeToFocus);

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
