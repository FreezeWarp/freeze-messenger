/* FreezeMessenger Copyright © 2011 Joseph Todd Parsons

 * This program is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
   along with this program.  If not, see <http://www.gnu.org/licenses/>. */

function previewUrl() {
  fileContent = $('#urlUpload').val();

  if (fileContent && fileContent != 'http://') {
    fileContainer = '<img src="' + fileContent + '" alt="" style="max-width: 200px; max-height: 250px; height: auto;" />';
    $('#preview').html(fileContainer);
    $('#imageUploadSubmitButton').removeAttr('disabled');
  }
  else {
    $('#imageUploadSubmitButton').attr('disabled','disabled');    
  }
}

function upFiles(id) {
  if (!id) { id = ''; }

  $('#imageUploadSubmitButton').attr('disabled','disabled');
  var fileInput = document.getElementById('fileUpload');

  if (typeof fileInput.files != 'undefined') {
    var files = fileInput.files;

    if (files.length > 0) {
      for (var i = 0; i < files.length; i++) {
        var file = files[i];
        handleFile(file,id);
      }
      return true;
    }
    else { return false; }
  }
  else {
    $('#preview').html('File analysis not supported. Please upgrade to a compatible browser.');
    $('#imageUploadSubmitButton').removeAttr('disabled');
    $('#urlUpload').attr('value','');
  }
}

function handleFile(file,id) {
  var fileName = file.name;
  var fileSize = file.size;

  if (!fileName.match(/\.(jpg|jpeg|gif|png|svg)$/i)) { // Make sure the file is an image.
    $('#preview').html('Wrong file type.');
  }
  else if (fileSize > 4 * 1000 * 1000) { // Make sure the file isn't too large.
    $('#preview').html('File too large.');
  }
  else if (typeof FileReader == 'undefined') {
    $('#preview').html('Preview not supported.');
    $('#imageUploadSubmitButton').removeAttr('disabled');
    $('#urlUpload').attr('value','');
  }
  else {
    var reader = new FileReader();
    
    reader.readAsDataURL(file);
    reader.onloadend = function() {
      var fileContent = reader.result;
      fileContainer = '<img src="' + fileContent + '" alt="" style="max-width: 200px; max-height: 250px; height: auto;" />';
      
      $('#preview').html(fileContainer);
    };
    
    $('#imageUploadSubmitButton').removeAttr('disabled');
    $('#urlUpload').attr('value','');
  }
}
/*
window.onload = function() {
  var dropbox = document.getElementById("messageList");
  dropbox.addEventListener("dragenter", dragenter, false);
  dropbox.addEventListener("dragleave", dragleave, false);
  dropbox.addEventListener("dragover", dragover, false);
  dropbox.addEventListener("drop", drop, false);

  function dragenter(e) {
    e.stopPropagation();
    e.preventDefault();
    $('#messageList').fadeTo(500,.6);
  }

  function dragleave(e) {
    $('#messageList').fadeTo(500,1);
  } 

  function dragover(e) {
    e.stopPropagation();
    e.preventDefault();
  }

  function drop(e) {
    e.stopPropagation();
    e.preventDefault();

    var dt = e.dataTransfer;
    var files = dt.files;

    upload(e);
    return true;
  }
}

function upload(event) {
  var data = event.dataTransfer;

  var boundary = '------multipartformboundary' + (new Date).getTime();
  var dashdash = '--';
  var crlf     = '\r\n';

  // Build RFC2388 string.
  var builder = '';

  builder += dashdash;
  builder += boundary;
  builder += crlf;
    
  var xhr = new XMLHttpRequest();
    
  // For each dropped file.
  for (var i = 0; i < data.files.length; i++) {
    var file = data.files[i];

    
    // Generate headers.          
    builder += 'Content-Disposition: form-data; name="fileUpload"';
    if (file.fileName) {
      builder += '; filename="' + file.fileName + '"';
    }
    builder += crlf;

    builder += 'Content-Type: application/octet-stream';
    builder += crlf;
    builder += crlf; 

    // Append binary data.
    if (typeof file.getAsBinary != 'undefined') {
      builder += file.getAsBinary();
      var continueTransfer = true;
    }
    else if (typeof file.readAsBinaryString != 'undefined') {
      var continueTransfer = true;
    }
    else {
      alert(typeof file.readAsBinaryString);
      alert('Unable to transfer the file: your browser does not support AJAX file transfer.');
      var continueTransfer = false;
    }
    
    if (continueTransfer) {
      builder += crlf;

      // Write boundary.
      builder += dashdash;
      builder += boundary;
      builder += crlf;
    
      // Mark end of the request.
      builder += dashdash;
      builder += boundary;
      builder += dashdash;
      builder += crlf;

      xhr.open("POST", "/uploadFile.php", true);
      xhr.setRequestHeader('content-type', 'multipart/form-data; boundary=' + boundary);
      xhr.sendAsBinary(builder);
 
      xhr.onload = function(event) { 
        // If we got an error display it.
        if (xhr.responseText) {
          $('body').append(xhr.responseText);
        }
      };
    }
  }
  
  // Prevent FireFox opening the dragged file.
  event.stopPropagation();
}*/
