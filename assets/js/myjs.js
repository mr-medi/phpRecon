addEventListener("load" , inicio , false);
function inicio()
{
    let botonComment = document.getElementById("comment");
    let botonBruter = document.getElementById("bruter");
    let botonForm = document.getElementById("form");
    let botonIndex = document.getElementById("index");
    let botonHeader = document.getElementById("header");
    let forms = document.getElementsByName('forms');
    let comments = document.getElementsByName('comments');
    let brutters = document.getElementsByName('bruter');
    let headers = document.getElementsByName('header');
    let urls = document.getElementsByName('url');
    let totalComments = document.getElementsByName('totalComments');
    let totalForms = document.getElementsByName('totalForms');

    //COMMENT
    $(botonComment).on("click touchstart" , function()
    {
        $(this).css('color','red');
        $(botonHeader).css('color','');
        $(botonIndex).css('color','');
        $(botonBruter).css('color','');
        $(botonForm).css('color','');
        //JUST SHOW THE PAGE WITH COMMENTS
        for(let i in urls)
        {
            let count = totalComments[i].textContent;
            if(typeof count == "string")
            {
                count = parseInt(count);
                if(count == 0)
                    urls[i].style.display = 'none';
            }
        }
        update();
        showWhat(comments);
        hideWhat(forms);
        hideWhat(brutters);
        hideWhat(headers);
    });

    //FORM
    $(botonForm).on("click touchstart" , function()
    {
        $(this).css('color','red');
        $(botonHeader).css('color','');
        $(botonIndex).css('color','');
        $(botonBruter).css('color','');
        $(botonComment).css('color','');
        //JUST SHOW THE PAGE WITH FORMS
        for(let i in urls)
        {
            let count = totalForms[i].textContent;
            if(typeof count == "string")
            {
                count = parseInt(count);
                console.log(count);
                if(count == 0)
                    urls[i].style.display = 'none';
            }
        }
        update();
        showWhat(forms);
        hideWhat(headers);
        hideWhat(comments);
        hideWhat(brutters);
    });

    //BRUTER
    $(botonBruter).on("click touchstart", function()
    {
        $(this).css('color','red');
        $(botonHeader).css('color','');
        $(botonIndex).css('color','');
        $(botonComment).css('color','');
        $(botonForm).css('color','');
        //JUST SHOW THE PAGE WITH FORMS
        for(let i in urls)
        {
            let count = totalForms[i].textContent;
            if(typeof count == "string")
            {
                count = parseInt(count);
                console.log(count);
                if(count == 0)
                    urls[i].style.display = 'none';
            }
        }
        update();
        hideWhat(headers);
        hideWhat(comments);
        showWhat(forms);
        showWhat(brutters);
    });

    //INDEX
    $(botonIndex).on("click touchstart" , function()
    {
        $(this).css('color','red');
        $(botonHeader).css('color','');
        $(botonComment).css('color','');
        $(botonForm).css('color','');
        $(botonBruter).css('color','');
        update();
        showWhat(headers);
        showWhat(urls);
        showWhat(comments);
        showWhat(forms);
        showWhat(brutters);
    });

    //
    //HEADERS
    $(botonHeader).on("click touchstart" , function()
    {
        $(this).css('color','red');
        $(botonComment).css('color','');
        $(botonForm).css('color','');
        $(botonBruter).css('color','');
        $(botonIndex).css('color','');
        update();
        showWhat(headers);
        showWhat(urls);
        hideWhat(comments);
        hideWhat(forms);
        hideWhat(brutters);
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

function update()
{
    forms = document.getElementsByName('forms');
    comments = document.getElementsByName('comments');
    brutters = document.getElementsByName('bruter');
    headers = document.getElementsByName('headers');
}
