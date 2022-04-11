/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/dashboard.scss';

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
                    toto.append($('<a href=\"'+ window.location.protocol + "//" + window.location.host + "/movie/" + data["movies"]["results"][i]["id"] +'\"><div class=\"testt\">'+data["movies"]["results"][i]["title"]+'</div></a>'))
            }
            // data["movies"].forEach((e) => {
            //     $("#container-testt").append(<div class=\"testt\">Le Seigneur des Anneaux Anneaux</div>\n")
            // })

        })
    })
})

