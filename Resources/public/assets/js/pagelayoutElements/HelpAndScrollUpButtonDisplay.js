class HelpAndScrollUpButtonDisplay {
  scrollUpButton;
  displayHelpButton;

  constructor() {
    this.scrollUpButton = document.querySelector(".scroll_up_button");
    this.displayHelpButton = document.getElementById("display_help");
  }

  setUpScrollUpButton() {
    if (this.scrollUpButton) {
      this.scrollUpButton.style.height = 0;

      this.scrollUpButton.onclick = (event) => {
        event.preventDefault();
        event.stopPropagation();

        window.scroll(0, 0);
      };

      document.addEventListener("scroll", this.handleScroll.bind(this));
    }
  }

  handleScroll() {
    if (window.scrollY === 0) {
      this.scrollUpButton.style.height = 0;
      this.scrollUpButton.children[0].classList.add("dont_display");
    } else if (
      this.scrollUpButton.children[0].classList.contains("dont_display")
    ) {
      this.scrollUpButton.children[0].classList.remove("dont_display");
      this.scrollUpButton.style.height = "50px";
    }
  }

  setUpHelp() {
    if (this.displayHelpButton) {
      const helpDisplay = document.querySelector(".cjw_help_container");
      const closeHelpButton = document.getElementById("close_help_button");

      if (helpDisplay && closeHelpButton) {
        this.displayHelpButton.onclick = (event) => {
          event.preventDefault();
          event.stopPropagation();

          helpDisplay.classList.remove("dont_display");
        };

        closeHelpButton.onclick = (event) => {
          event.preventDefault();
          event.stopPropagation();

          helpDisplay.classList.add("dont_display");
        };

        document.onkeydown = (event) => {
          if (event.key === "Escape") {
            event.preventDefault();
            event.stopPropagation();

            helpDisplay.classList.add("dont_display");
          }
        };
      }
    }
  }
}
