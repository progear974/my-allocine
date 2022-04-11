import './styles/detail-movie.scss'


function set_visibility_liked() {
    var pathArray = window.location.pathname.split('/');
    const url = window.location.protocol + "//" + window.location.host + "/is_liked/" + pathArray[2];
    const request = new Request(url, {method: 'POST'});
    fetch(request).then(response => response.json()).then(data => {
        console.log(data["is_liked"])
        if (data["is_liked"]) {
            $('#like').attr('style', 'display:block;');
            $('#unlike').attr('style', 'display:none;');
        } else {
            $('#like').attr('style', 'display:none;');
            $('#unlike').attr('style', 'display:block;');        }
    });
}

function set_visibility_viewed() {
    var pathArray = window.location.pathname.split('/');
    const url = window.location.protocol + "//" + window.location.host + "/is_viewed/" + pathArray[2];
    const request = new Request(url, {method: 'POST'});
    fetch(request).then(response => response.json()).then(data => {
        console.log(data["is_viewed"])
        if (data["is_viewed"]) {
            $('#viewed').attr('style', "display:'';");
            $('#not_viewed').attr('style', 'display:none;');
        } else {
            $('#viewed').attr('style', 'display:none;');
            $('#not_viewed').attr('style', "display:'';");        }
    });
}

$(document).ready(function () {

    $("#search").on('input', (e) => {
        e.preventDefault();
        // console.log(e.target.value);
        var pathArray = window.location.pathname.split('/');
        var url = new URL(window.location.protocol + "//" + window.location.host + "/find_movie_from_title");
        var params = {title: e.target.value}; // or:
        url.search = new URLSearchParams(params).toString();

        fetch(url).then(response => response.json()).then(data => {

            const toto = $(".container-testt");
            toto.html("");
            for (let i = 0; i < 5; i++) {
                console.log(data);
                if (data["movies"] !== null && data["movies"]["results"] !== null && data["movies"]["results"][i] !== null)
                    toto.append($('<a href=\"'+ window.location.protocol + "//" + window.location.host + "/movie/" + data["movies"]["results"][i]["id"] + '\"><div class=\"testt\">'+data["movies"]["results"][i]["title"]+'</div></a>'))
            }
            // data["movies"].forEach((e) => {
            //     $("#container-testt").append(<div class=\"testt\">Le Seigneur des Anneaux Anneaux</div>\n")
            // })

        })
    })


    set_visibility_liked();
    set_visibility_viewed();

    $('#like').on("click", (e) => {
        e.preventDefault();
        var pathArray = window.location.pathname.split('/');
        const url = window.location.protocol + "//" + window.location.host + "/unlike/" + pathArray[2];
        const request = new Request(url, {method: 'POST'});
        fetch(request);
        set_visibility_liked();
    })
    $('#unlike').on("click", (e) => {
        e.preventDefault();
        var pathArray = window.location.pathname.split('/');
        const url = window.location.protocol + "//" + window.location.host + "/new_like/" + pathArray[2];
        const request = new Request(url, {method: 'POST'});
        fetch(request);
        set_visibility_liked();
    })
    $('#viewed').on("click", (e) => {
        e.preventDefault();
        var pathArray = window.location.pathname.split('/');
        const url = window.location.protocol + "//" + window.location.host + "/unview/" + pathArray[2];
        const request = new Request(url, {method: 'POST'});
        fetch(request);
        set_visibility_viewed();
    })
    $('#not_viewed').on("click", (e) => {
        e.preventDefault();
        var pathArray = window.location.pathname.split('/');
        const url = window.location.protocol + "//" + window.location.host + "/new_view/" + pathArray[2];
        const request = new Request(url, {method: 'POST'});
        fetch(request);
        set_visibility_viewed();
    })

})

