window.codedrop = function() { // ... ends with: return codedrop_global }();

// private variables
var euid = websheets.authinfo.username, uid = websheets.authinfo.username;

var codedrop_global = {}; // will become window.codedrop, used as a namespace

function initialize_assignment_codedrop(slug, course, state) {
    var arrScripts = document.getElementsByTagName('script');
    var currScript = arrScripts[arrScripts.length - 1];
    var containerdiv = $(currScript).closest('div');
    var ws = new AssignmentCodedrop(slug, course, containerdiv, state);
}

function AssignmentCodedrop(slug, course, div, state) {
    window.codedrop.instance = this; // for debugging
    this.slug = slug;
    this.div = div;
    this.state = state;
    this.course = course;
    this.clickable = true;
    this.fs_markup = "<input type='file' class='file-select'>";
    
    $(this.div).addClass('codedrop');
    $(this.div).addClass('clickable');

    $(this.div).html("\n\
<div class='preamble'> \n\
<p class='due'>Due: " + this.dueDate().toDateString() + "<span class='duetime'> 11:59:59 PM Pacific Time </span>\n\
<div class='blurb'>Note: You can resubmit and recheck as often as you want, \n\
but only your last submission will be graded. \n\
<p>Assignments up to 24 hours late lose 25% of the assignment's value.\n\
Assignments up to 48 hours late lose 50% of the assignment's value. \n\
After 48 hours you cannot submit. You get two grace days per semester, but use them sparingly! \n\
</div> \n\
</div> \n\
<h3 class='codedrop-header'>Assignment Files</h3> \n\
<div class='su-only'> \n\
<b>You're a super user, <span class='whowasi'></span>! \n\
Currently viewing the work of:</b> <tt class='whoami'></tt> <button class='masquerade'>Change</button> \n\
<button class='masquerade-refresh'>Refresh</button> \n\
</div> \n\
<table><tbody class='filetable'> \n\
<div class='labtime lab-only' style='text-align:center'></div> \n\
</tbody></table> \n\
<table><tbody> \n\
<tr class='grade lab-only' style='text-align:center' colspan='99'><td>Grade info here.</td></tr> \n\
<tr class='pa-only'><td><span class='button check'>Check My Submission</span></tr> \n\
<tr class='su-only pa-only'><td><span class='button checklong'>(Super-User Only) Grading Script Output</span></tr> \n\
</tbody></table> \n\
<table><tbody class='gradetable lab-only su-only'> \n\
<tr class='su-only lab-only'><td colspan=99><span class='button enter-grade'>(Super-User Only) Submit Grade</span></tr> \n\
</tbody></table> \n\
<div class='status'></div> " + this.fs_markup 
);

    if (this.state.kind == "Lab") {
       $(this.div).addClass('lab');
       $(this.div).find(".duetime").hide();
       if (this.state.blurbs.lab_preamble)
          $(this.div).find(".preamble .blurb").html(this.state.blurbs.lab_preamble)
       else
          $(this.div).find(".preamble .blurb").html("You must demonstrate it <b>in person</b> in your registered lab section <i>or</i> at a TA or CP's office hours. <i>You will get 0 points if you don't demo it in person.</i> You are allowed to wait until check-in is complete before uploading your final version. You can re-upload as many times as you like.");
    }
    else if (this.state.kind == "Assignment" || this.state.kind == "Exam" )
       $(this.div).addClass('pa');
    if (state["is_super"]) 
       $(this.div).addClass('su');

    if (this.state.kind) {
       $(this.div).find(".codedrop-header").html(this.state.kind+" Files");
    }

    if (this.state.codedrop_pledge) {
       $(this.div).find(".codedrop-header").after("<input type='checkbox' class='pledge' id='pledge'></input><label for='pledge'>All code that I am submitting is my own and not the result of copying another's work. Similarly, any help I received on this assignment did not involve step-by-step instructions from another person but represents my creative solution to the given problem.</label>");
    }


    var this_au = this;
 
    if (this.state.rubric) {
       //console.log(this.state.rubric);
       var t = $(this_au.div).find('.gradetable')[0];
       for (var i=0; i<this.state.rubric.length; i++) {
          var row = t.insertRow(-1);
          var slug = "gradefor-" + this.state.rubric[i].slug;
          var options = "";
          for (var j=0; j<=this.state.rubric[i].points; j++)
             options += "<span class='box'><input class='points"+j+"' type='radio'"+(j==0?" checked ":"")
             +" name='"+slug+"' value='"+j+"'>"+j+"</span>";
          row.insertCell(0).innerHTML = this.state.rubric[i].desc;
          row.insertCell(1).innerHTML = options;
       }
       t.insertRow(-1).innerHTML = '<tr><td>Optional Grader-Only Remarks<td><textarea class="remarks"></textarea></tr>';
    }

    this.resetTable();

    $(this_au.div).on('click', '.button', 
                      function(event) {this_au.onButtonClick(event);});
        
    $(this_au.div).on('change', '.file-select',
                      function() {this_au.afterFilesSelected();});

    if (state["is_super"]) {
        $(this_au.div).find(".whoami").html(euid);
        $(this_au.div).find(".whowasi").html(uid);
        $(this_au.div).find(".masquerade").on
        ("click", 
         function() 
         {
             var tmp = prompt("Please enter another username");
             if (tmp == null) return;
             euid = tmp;
             euid += "@usc.edu";
             euid = euid.replace("@usc.edu@usc.edu", "@usc.edu");
             $(this_au.div).find(".whoami").html(euid);             
             this_au.submitFD(null, 'state', 'Getting info&hellip;');
             return;
         });
        $(this_au.div).find(".masquerade-refresh").on
        ("click", 
         function() 
         {
             this_au.submitFD(null, 'state', 'Getting info&hellip;');
             return;
         });
    }
}

AssignmentCodedrop.prototype.dueDate = function() {
    var date = this.state.due_date;
    var monthnum = new Date(date[1]+" 28, 2000").getMonth()
    return new Date(date[0], monthnum, date[2], 23, 59);
}

AssignmentCodedrop.prototype.setClickable = function(val) {
    if (val) {
        $(this.div).addClass('clickable');
        this.clickable = true;
    }
    else {
        $(this.div).removeClass('clickable');
        this.clickable = false;
    }
}


AssignmentCodedrop.prototype.resetTable = function() {
    if (this.state.labtime) {
       $(this.div).find('.labtime').html(this.state.labtime);
    }
   
    $(this.div).find('.filetable').html("");
    
    var files = this.state.files;

    for (var i=0; i<files.length; i++) {
        var newRow = '<tr>';
        newRow += '<td class="w15"><tt>' + files[i].filename + '</tt>';
        if (files[i].submitted) {
            newRow += '<td class="w40">' + files[i].description;
            newRow += '<td class="w15"><span class="button replace">replace</button></td>';
            newRow += '<td class="w15"><span class="button delete">delete</button></td>';
            newRow += '<td class="w15"><span class="button view">view</button></td>';
        }
        else {
            newRow += '<td class="w40">not submitted';
            newRow += '<td class="w15"><span class="button upload">upload</button></td>';
            newRow += '<td class="w15"><td class="w15">';
        }
        newRow += '</tr>';
        $(this.div).find('.filetable').append(newRow);
    }

    if (this.state.kind == "Lab") {
       this.setGradeObject(this.state.grade);
    }

}

AssignmentCodedrop.prototype.getGradeObject = function() {
   var result = {};
   var total = 0;
   result.points = {};
   for (var i=0; i<this.state.rubric.length; i++) {
      var slug = this.state.rubric[i].slug;
      var val = parseInt($(this.div).find('input[name="gradefor-'+slug+'"]:checked').val());
      result.points[slug] = val;
      total += val;
   }
   result['total'] = total;
   result['remarks'] = $(this.div).find('.remarks').val();
   return result;
}

AssignmentCodedrop.prototype.setGradeObject = function(obj) {
   if (obj == null) {
      $(this.div).find(".grade").html("<i>No grade entered so far.</i>");
      $(".points0").prop("checked",true);
      return;
   }
   var gradeInfo = "<i>Grade</i>: <b>" + obj.total + "/10</b> (entered at " + obj.time + " by <tt>" + obj.grader+"</tt>)";
   if (this.daysLate() > 0) {
      if (!this.state.extension) {
         gradeInfo += " <i>Deadline is past.</i>";
      }
      else {
         gradeInfo += " <i>Student has extension.</i>";
      }
   }
   $(this.div).find(".grade").html(gradeInfo);
   
   
   if (this.state["is_super"] && this.state.grade.points) {
      $(this.div).find('.remarks').val(obj.remarks);
      for (var i=0; i<this.state.rubric.length; i++) {
         var slug = this.state.rubric[i].slug;
         $(this.div).find('input[name="gradefor-'+slug+'"].points'+obj.points[slug]).prop("checked", true);
      }
   }
}

AssignmentCodedrop.prototype.daysLate = function(event) {
   var now = new Date();
   var daysLate = now - this.dueDate() - 2*1000*60; // 2 minute grace period for javascript
   daysLate /= 1000*60*60*24;
   daysLate = Math.ceil(daysLate);
   return daysLate;
}

AssignmentCodedrop.prototype.onButtonClick = function(event) {
    if (!this.clickable) return;

    if ($(event.target).hasClass('upload')
        || $(event.target).hasClass('replace')
        || $(event.target).hasClass('delete')) {
        if (euid != uid) {
            alert("You cannot edit someone else's files.");
            return;
        }

        var daysLate = this.daysLate();

        if (this.state.kind == 'Lab' && daysLate > 0 &&! (this.state.is_super || this.state.extension)) {
            alert("You cannot submit labs late unless you have an extension.");
        }

        var maxDaysLate = 2;

        if (daysLate > maxDaysLate &&! (this.state.is_super || this.state.extension)) {
            alert("You cannot submit assignments more than "+maxDaysLate+" days late unless you have an extension.");
            return;
        }
        else if (daysLate > 0) {
            var r = confirm("Warning: the assignment is now " 
                            + daysLate + " day(s) late.");
            if (!r) return;
        }
    }
    
    var row = $(event.target).closest('tr');
    this.expected_file = row.parent().children().index(row[0]);
    
    if ($(event.target).hasClass('check')) {
        this.submitFD(null, 'check', 'Checking your submission&hellip;');
        return;
    }
    
    if ($(event.target).hasClass('checklong')) {
        this.submitFD(null, 'checklong', 'Submitting for grading&hellip;');
        return;
    }
    
    if ($(event.target).hasClass('enter-grade')) {
       this.submitFD(null, 'enter-grade', 'Entering grade&hellip;', 
                     [["grade", JSON.stringify(this.getGradeObject())]]);
       return;
    }
    
    if ($(event.target).hasClass('delete')) {
        var fd = new FormData();
        fd.append('filename', this.state.files[this.expected_file].filename);
        this.submitFD(fd, 'delete', 
                      'Deleting '
                      +this.state.files[this.expected_file].filename
                      +'&hellip;');
        return;            
    }
    
    if ($(event.target).hasClass('upload') ||
        $(event.target).hasClass('replace')) {
        if (this.state.codedrop_pledge) {
           if (!$(this.div).find('.pledge')[0].checked) {
              alert('Please confirm that you are submitting your own work, by clicking the checkbox.');
              return;
           }
        }
 
       // launch file selector, it will upload and ajax after selection
        $(this.div).find('.file-select')[0].click();
        return;
    }
    
    if ($(event.target).hasClass('view')) {
        // complicated b/c trying to use POST required by wp, & want new window
        var f = document.createElement("form");
        f.setAttribute("method", "post");
        f.setAttribute("action", submit_ajax_url);
        f.setAttribute("target", "_blank");
        f.setAttribute("style", "display:none");
        f.setAttribute("class", "tempform");
        var data = [['operation', 'view'],
                    ['ajax_uid_intended', websheets.authinfo.username],
                    ['assignment', this.slug],
                    ['course', this.course],
                    ['filename', 
                     this.state.files[this.expected_file].filename],
                    ['user', euid]]; // authentication will pass uid
        
        for (var i=0; i<data.length; i++) {
            var inp = document.createElement("input");
            inp.setAttribute("name", data[i][0]);
            inp.setAttribute("value", data[i][1]);
            f.appendChild(inp);
        }
        $('body').append(f); // to make firefox happy
        f.submit();
        $('.tempform').remove();
        return;            
    }
}

AssignmentCodedrop.prototype.status = function(markup) {
    $(this.div).find('.status').html(markup);
}

AssignmentCodedrop.prototype.afterFilesSelected = function() {
    var this_au = this;

    var fs = $(this.div).find('.file-select')[0];
    // to avoid bug where second selection of same file has no effect:
    $(fs).replaceWith(this.fs_markup);

    if (fs.files.length == 0) 
        return; // no files selected, nothing to do
    
    var required = this.state.files[this.expected_file].filename;
    var file = fs.files[0];
    if (file.name != required) {
        alert("Couldn't send, wrong file. You clicked the button to upload\n"
             + required + '\nbut the file you selected was\n' + file.name); 
        return;
    }

    if (file.size > 20000) {
        alert("Cannot upload a file larger than 20000 bytes.");
        return;
    }

    var fd = new FormData();
    fd.append('file', file);
    fd.append('filename', file.name);
    this.submitFD(fd, 'upload', 'Uploading file&hellip;');
}

//form_extras should be a list of pairs
AssignmentCodedrop.prototype.submitFD = function(fd, operation, wait_text, form_extras) {
    var this_au = this;

    this.status(wait_text);
    this.setClickable(false);

    if (!fd)
        fd = new FormData();
    fd.append('operation', operation);
    fd.append('assignment', this.slug);
    fd.append('course', this.course);
    fd.append('user', euid);
    fd.append('ajax_uid_intended', websheets.authinfo.username);
    if (form_extras)
       for (var i=0; i<form_extras.length; i++)
          fd.append(form_extras[i][0], form_extras[i][1]);

    $.ajax({url: submit_ajax_url,
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json',
            type: 'POST',
            success: function(data) { 
                this_au.ajax_success(data); 
            },
            error: function(jqXHR, textStatus, errorThrown) {
                if (textStatus == "parsererror") {
                    this_au.status(jqXHR.responseText+"<br>"+
                                  "Please feel free to copy this message and "+
                                  "send it to an administrator.");
                }
                else {
                    this_au.status("<b>Could not send your request.</b> \n\
Check your internet connection.");
                    if (textStatus == "timeout") 
                        alert("Could not send your request. \n\
Check your internet connection." + textStatus);
                }
                this_au.setClickable(true);
            }
           });
};

AssignmentCodedrop.prototype.ajax_success = function(data) {
    this.setClickable(true);
    if (data.status) {
        this.status(data.status);
    }
    else this.status("Unknown status.");
    if (data.state) {
        this.state = data.state;
        this.resetTable();
    }
}

$(function() {
   $("body").on("click", ".box", function(event) {
      $(event.target).find("input").prop("checked", true);
   });
});

codedrop_global.initialize_assignment = initialize_assignment_codedrop;
return codedrop_global;
}();
