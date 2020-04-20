addEventListener("load" , inicio , false);
function inicio()
{
    let botonComment = document.getElementById("comment");
    let botonBruter = document.getElementById("bruter");
    let botonForm = document.getElementById("form");
    let botonIndex = document.getElementById("index");
    let forms = document.getElementsByName('forms');
    let comments = document.getElementsByName('comments');
    let brutters = document.getElementsByName('bruter');

    //
    $(botonComment).on("click touchstart" , function()
    {
        $(this).css('color','red');
        $(botonIndex).css('color','');
        $(botonBruter).css('color','');
        $(botonForm).css('color','');
        forms = document.getElementsByName('forms');
        comments = document.getElementsByName('comments');
        brutters = document.getElementsByName('bruter');
        showWhat(comments);
        hideWhat(forms);
        hideWhat(brutters);
    });

    //
    $(botonForm).on("click touchstart" , function()
    {
        $(this).css('color','red');
        $(botonIndex).css('color','');
        $(botonBruter).css('color','');
        $(botonComment).css('color','');
        forms = document.getElementsByName('forms');
        comments = document.getElementsByName('comments');
        brutters = document.getElementsByName('bruter');
        showWhat(forms);
        hideWhat(comments);
        hideWhat(brutters);
    });

    //
    $(botonBruter).on("click touchstart", function()
    {
        $(this).css('color','red');
        $(botonIndex).css('color','');
        $(botonComment).css('color','');
        $(botonForm).css('color','');
        forms = document.getElementsByName('forms');
        comments = document.getElementsByName('comments');
        brutters = document.getElementsByName('bruter');
        hideWhat(comments);
        hideWhat(forms);
        showWhat(brutters);
    });

    //
    $(botonIndex).on("click touchstart" , function()
    {
        $(this).css('color','red');
        $(botonComment).css('color','');
        $(botonForm).css('color','');
        $(botonBruter).css('color','');
        forms = document.getElementsByName('forms');
        comments = document.getElementsByName('comments');
        brutters = document.getElementsByName('bruter');
        showWhat(comments);
        showWhat(forms);
        showWhat(brutters);
    });
}

function showWhat(array)
{
    for(let i in array)
    {
        if(typeof array[i] == "object")
            array[i].style.display='';
    }
}

function hideWhat(array)
{
    for(let i in array)
    {
        if(typeof array[i] == "object")
            array[i].style.display='none';
    }
}
