document.addEventListener("DOMContentLoaded", () => {
  const dropzone = document.getElementById("dropzone");
  const browseButton = document.getElementById("browse-button");
  const fileInput = document.getElementById("file-input");
  const selectedFile = document.getElementById("selected-file");

  if (!dropzone || !browseButton || !fileInput || !selectedFile) {
    return;
  }

  const updateSelection = (files) => {
    if (!files || files.length === 0) {
      selectedFile.textContent = "No file selected";
      return;
    }
    selectedFile.textContent = `Selected: ${files[0].name}`;
  };

  browseButton.addEventListener("click", () => fileInput.click());
  fileInput.addEventListener("change", () => updateSelection(fileInput.files));

  ["dragenter", "dragover"].forEach((eventName) => {
    dropzone.addEventListener(eventName, (event) => {
      event.preventDefault();
      dropzone.classList.add("dragover");
    });
  });

  ["dragleave", "drop"].forEach((eventName) => {
    dropzone.addEventListener(eventName, (event) => {
      event.preventDefault();
      dropzone.classList.remove("dragover");
    });
  });

  dropzone.addEventListener("drop", (event) => {
    const files = event.dataTransfer.files;
    fileInput.files = files;
    updateSelection(files);
  });
});
