class CopyButtonUtility {
  copyButtons;
  copyInputField;

  constructor() {
    this.copyButtons = document.querySelectorAll(".copy_param_name");
    this.copyInputField = document.querySelector("#cjw_copy_to_clipboard");
  }

  setUpCopyButtons() {
    if (
      this.copyButtons &&
      this.copyInputField &&
      this.copyButtons.length > 0
    ) {
      for (const button of this.copyButtons) {
        button.onclick = this.handleCopyClickEvent.bind(this);
      }

      document.addEventListener(
        "pathKeysAdded",
        this.handleAdditionOfPathKeys.bind(this)
      );
    }
  }

  handleCopyClickEvent(event) {
    event.preventDefault();
    event.stopPropagation();

    const pressedCopyButton = event.currentTarget;
    this.copyParameterName(pressedCopyButton);
  }

  handleCopyPathInfoClickEvent(event) {
    event.preventDefault();
    event.stopPropagation();

    const pathKey = event.currentTarget;
    this.copyFileLocationPath(pathKey);
  }

  handleAdditionOfPathKeys(event) {
    event.preventDefault();
    event.stopPropagation();

    if (event.detail.pathInfoCarrier) {
      const pathParent = event.detail.pathInfoCarrier;
      const pathKeys = pathParent.querySelectorAll(".path_info_key");

      for (const pathKey of pathKeys) {
        pathKey.addEventListener(
          "click",
          this.handleCopyPathInfoClickEvent.bind(this)
        );
      }
    }
  }

  copyParameterName(pressedCopyButton) {
    if (pressedCopyButton) {
      const copyParent = pressedCopyButton.parentElement;
      const pathInfo = copyParent.querySelector(".location_info");
      const buttonImage = pressedCopyButton.querySelector("svg");

      if (pathInfo) {
        this.copyInputField.value = pathInfo.getAttribute("fullparametername");
        this.copyInputField.classList.remove("dont_display");
        this.copyInputField.select();
        document.execCommand("copy");
        this.copyInputField.classList.add("dont_display");
        buttonImage.style.fill = "#52bfec";
        pressedCopyButton.title = "key copied";

        setTimeout(() => {
          buttonImage.style.fill = "";
          pressedCopyButton.title = "copy key";
        }, 3000);
      }
    }
  }

  copyFileLocationPath(pathKey) {
    if (pathKey) {
      pathKey.title = "copied";
      this.copyInputField.value = pathKey.getAttribute("path");
      this.copyInputField.classList.remove("dont_display");
      this.copyInputField.select();
      document.execCommand("copy");
      this.copyInputField.classList.add("dont_display");
      pathKey.style.backgroundColor = "#52bfec";

      setTimeout(() => {
        pathKey.style.backgroundColor = "";
        pathKey.title = "copy path";
      }, 3000);
    }
  }
}
