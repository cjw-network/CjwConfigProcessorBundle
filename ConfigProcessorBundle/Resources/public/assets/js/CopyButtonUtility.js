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
    }
  }

  handleCopyClickEvent(event) {
    event.preventDefault();
    event.stopPropagation();

    const pressedCopyButton = event.currentTarget;
    this.copyParameterName(pressedCopyButton);
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

        setTimeout(() => {
          buttonImage.style.fill = "";
        }, 2000);
      }
    }
  }

  copyFileLocationPath(pressedCopyButton) {
    if (pressedCopyButton) {
      const copyParent = pressedCopyButton.parentElement;
      const buttonImage = pressedCopyButton.querySelector("svg");

      if (copyParent) {
        this.copyInputField.value = copyParent.innerText;
        this.copyInputField.classList.remove("dont_display");
        this.copyInputField.select();
        document.execCommand("copy");
        this.copyInputField.classList.add("dont_display");
        buttonImage.style.fill = "#52bfec";

        setTimeout(() => {
          buttonImage.style.fill = "";
        }, 2000);
      }
    }
  }
}
