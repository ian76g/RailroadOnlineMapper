class Mapper {
    constructor(json, cookies) {
        this.svgNS = "http://www.w3.org/2000/svg";
        this.svgTag = null;
        this.shapes = [];
        this.json = json;
        this.drawMaxSlope = true;

        this.imageWidth = 8000;
        this.minX = -200000;
        this.maxX = 200000;
        this.minY = -200000;
        this.maxY = 200000;
        this.x = this.maxX - this.minX;
        this.y = this.maxY - this.minY;
        this.max = Math.max(this.x, this.y);
        this.scale = (this.imageWidth * 100 / this.max);
        this.switchRadius = 80;
        this.engineRadius = 6 * this.scale;
        this.turnTableRadius = (10 / 2.2107077) * this.scale;
        this.imx = this.x / 100 * this.scale;
        this.imy = this.y / 100 * this.scale;
        this.maxSlope = 0;
        this.allLabels = [[0, 0]];
        this.allCurveLabels = [[[0, 0]], [[0, 0]], [[0, 0]], [[0, 0]]];
        this.initialTreesDown = 1750;

        this.config = {
            labelPrefix: cookies.get('labelPrefix') || '..'
        }
    }

    deg2rad(degrees) {
        const pi = Math.PI;
        return degrees * (pi / 180);
    }

    thingy(angle, x, y) {
        return [x, y];
        x += Math.cos(this.deg2rad(angle)) * 2;
        y += Math.sin(this.deg2rad(angle));

        return [x, y];
    }

    drawSVG(htmlElement, industryHtml) {
        this.svgTag = document.getElementById(htmlElement);
        this.getReplantableTrees();
        this.getTracksAndBeds();
        this.getSwitches();
        this.getTurntables();
        this.getIndustries(industryHtml);
        this.getWaterTowers();
        this.getRollingStock();

        this.populatePlayerTable();

        for (const shape of this.shapes) {
            this.svgTag.appendChild(shape);
        }
    }

    createSVGElement(type, attrs) {
        const element = document.createElementNS(this.svgNS, type);
        for (const attr in attrs) {
            element.setAttribute(attr, attrs[attr].toString());
        }
        return element;
    }

    populatePlayerTable() {
        if (!('Players' in this.json)) {
            return null
        }
        const playerGroup = document.createElementNS(this.svgNS, "g");
        playerGroup.setAttribute("class", "players_default");

        const editPlayersTable = document.getElementById("editPlayersTable");

        for (let index = 0; index < this.json['Players'].length; index++) {
            let player = this.json['Players'][index];

            let playerEditInfoRow = document.createElement("tr");

            let playerEditValue = document.createElement("td");
            let playerEditTextNode = document.createTextNode(player['Name']);
            playerEditValue.appendChild(playerEditTextNode);
            playerEditInfoRow.appendChild(playerEditValue);

            let playerEditXpValue = document.createElement("td");
            let playerEditXpInput = document.createElement("input");
            playerEditXpInput.size = 5;
            playerEditXpInput.maxLength = 15;
            playerEditXpInput.name = "xp_" + index;
            playerEditXpInput.value = player['Xp'];
            playerEditXpValue.appendChild(playerEditXpInput);
            playerEditInfoRow.appendChild(playerEditXpValue);

            let playerEditMoneyValue = document.createElement("td");
            let playerEditMoneyInput = document.createElement("input");
            playerEditMoneyInput.size = 5;
            playerEditMoneyInput.maxLength = 15;
            playerEditMoneyInput.name = "money_" + index;
            playerEditMoneyInput.value = player['Money'];
            playerEditMoneyValue.appendChild(playerEditMoneyInput);
            playerEditInfoRow.appendChild(playerEditMoneyValue);

            let playerEditNearValue = document.createElement("td");
            let playerEditNearTextNode = document.createTextNode("Unknown");
            if ('Industries' in this.json) {
                playerEditNearTextNode = document.createTextNode(this._nearestIndustry(player['Location'], this.json['Industries'], false));
            }
            playerEditNearValue.appendChild(playerEditNearTextNode);
            playerEditInfoRow.appendChild(playerEditNearValue);

            let playerEditDeleteValue = document.createElement("td");

            if (index === 0) {
                let playerEditDeleteInput = document.createTextNode("Unknown");
                playerEditDeleteValue.appendChild(playerEditDeleteInput);
            } else {
                let playerEditDeleteInput = document.createElement("input");
                playerEditDeleteInput.type = "checkbox"
                playerEditDeleteInput.name = "deletePlayer_" + index;
                playerEditDeleteValue.appendChild(playerEditDeleteInput);
            }

            /*
            Radius der x-Achse der Ellipse
Radius der y-Achse der Ellipse
Rotation der x-Achse der Ellipse in Grad (0: keine Rotation)
large-arc-flag:
kurzer Weg um die Ellipse: 0
langer Weg um die Ellipse: 1
sweep-flag:
Zeichnung entgegen den Uhrzeigersinn: 0
Zeichnung mit dem Uhrzeigersinn: 1
(x y)-Koordinaten des Endpunktes
<path d="M 674 79 A 150 180 45 1 0 582 350" stroke="red" fill="none"/>
             start = 10 Grad
             ende = 250 Grad
             */
            if (player['Location'] === undefined || player['Location'][0] === undefined) {
                player['Location'] = [];
                player['Location'][0] = 0;
                player['Location'][1] = 0;
            }
            let px = (this.imx - ((player['Location'][0] - this.minX) / 100 * this.scale));
            let py = (this.imy - ((player['Location'][1] - this.minY) / 100 * this.scale));
            let ps = this.polarToCartesian(px, py, 3, 20 + player['Rotation']);
            let pe = this.polarToCartesian(px, py, 3, 340 + player['Rotation']);
            let playerCircle = document.createElementNS(this.svgNS, "path");
            let path = 'M ' + ps.x + ' ' + ps.y + ' A 3 3 0 1 0 ' + pe.x + ' ' + pe.y;
            path += ' A 1 1 0 1 0 ' + ps.x + ' ' + ps.y;
            playerCircle.setAttribute("d", path);
            playerCircle.setAttribute("fill", "pink");
            playerCircle.setAttribute("stroke", "black");
            playerCircle.setAttribute("stroke-width", "1");

            let title = document.createElementNS(this.svgNS, "title");
            title.textContent = player['Name'].replace(/<\/?[^>]+(>|$)/g, "");
            playerCircle.appendChild(title);

            playerGroup.appendChild(playerCircle);

            playerEditInfoRow.appendChild(playerEditDeleteValue);

            editPlayersTable.appendChild(playerEditInfoRow);
        }
        this.shapes.push(playerGroup);

    }

    /**
     *
     */
    getTracksAndBeds() {
        // const tracksAndBedsGroup = document.createElementNS(this.svgNS, "g");
        const tracksGroup = document.createElementNS(this.svgNS, "g");
        const bedsGroup = document.createElementNS(this.svgNS, "g");
        const ironBridgeGroup = document.createElementNS(this.svgNS, "g");
        const maxSlopeLabelGroup = document.createElementNS(this.svgNS, "g");
        tracksGroup.setAttribute("class", "tracks");
        bedsGroup.setAttribute("class", "beds");
        ironBridgeGroup.setAttribute("class", "ironOverWood");
        maxSlopeLabelGroup.setAttribute("class", "maxSlopeLabel");

        const slopeLabelGroup = Array(
            document.createElementNS(this.svgNS, "g"),
            document.createElementNS(this.svgNS, "g"),
            document.createElementNS(this.svgNS, "g"),
            document.createElementNS(this.svgNS, "g"),
            document.createElementNS(this.svgNS, "g"),
            document.createElementNS(this.svgNS, "g"),
            document.createElementNS(this.svgNS, "g"),
            document.createElementNS(this.svgNS, "g"),
            document.createElementNS(this.svgNS, "g")
        );

        slopeLabelGroup[0].setAttribute("class", "slopeLabel0");
        slopeLabelGroup[1].setAttribute("class", "slopeLabel1");
        slopeLabelGroup[2].setAttribute("class", "slopeLabel2");
        slopeLabelGroup[3].setAttribute("class", "slopeLabel3");
        slopeLabelGroup[4].setAttribute("class", "slopeLabel4");
        slopeLabelGroup[5].setAttribute("class", "slopeLabel5");
        slopeLabelGroup[6].setAttribute("class", "slopeLabel6");
        slopeLabelGroup[7].setAttribute("class", "slopeLabel7");
        slopeLabelGroup[8].setAttribute("class", "slopeLabel8");
        const slopeLabelSilly = document.createElementNS(this.svgNS, "text");
        const textNodeSilly = document.createTextNode("");
        slopeLabelSilly.setAttribute("x", "0");
        slopeLabelSilly.setAttribute("y", "0");
        slopeLabelSilly.appendChild(textNodeSilly);
        slopeLabelGroup[0].appendChild(slopeLabelSilly);
        slopeLabelGroup[1].appendChild(slopeLabelSilly);
        slopeLabelGroup[2].appendChild(slopeLabelSilly);
        slopeLabelGroup[3].appendChild(slopeLabelSilly);
        slopeLabelGroup[4].appendChild(slopeLabelSilly);
        slopeLabelGroup[5].appendChild(slopeLabelSilly);
        slopeLabelGroup[6].appendChild(slopeLabelSilly);
        slopeLabelGroup[7].appendChild(slopeLabelSilly);
        slopeLabelGroup[8].appendChild(slopeLabelSilly);

        const drawOrder = {}; // [type, stroke-width, stroke]
        drawOrder[1] = [1, 15, 'darkkhaki']; // variable bank
        drawOrder[2] = [2, 15, 'darkkhaki']; // constant bank
        drawOrder[5] = [5, 15, 'darkgrey'];  // variable wall
        drawOrder[6] = [6, 15, 'darkgrey'];  // constant wall
        drawOrder[3] = [3, 15, 'orange'];    // wooden bridge
        drawOrder[7] = [7, 15, 'lightblue']; // iron bridge
        drawOrder[4] = [4, 2, 'black'];      // trendle track
        drawOrder[0] = [0, 2, 'black'];      // track        darkkhaki, darkgrey, orange, blue, black

        let slopecoords = [0, 0];

        let order = [1, 2, 5, 6, 3, 7, 4, 0];
        var oi;

        for (oi = 0; oi < 8; oi++) {
            if ('Splines' in this.json) {
                let splineIndex = -1;
                for (const spline of this.json['Splines']) {
                    splineIndex++;
                    let type = spline['Type'];
                    if(type !== order[oi]) continue;
                    let entry = drawOrder[type];
                    let [, strokeWidth, stroke] = entry;

                    let multiplier = 1;
                    if ([1, 2, 5, 6].indexOf(type) > -1) {
                        let x = this.getZDistanceToNearestTrack(spline['Segments'][0]);
                        if (x > 0) {
                            multiplier = Math.round(x / 180);
                        }
                    }
                    strokeWidth = strokeWidth * multiplier;

                    let segments = spline['Segments'];
                    if ([1, 2, 5, 6, 3, 7].indexOf(type) > -1) {
                        const bedSegment = document.createElementNS(this.svgNS, 'path');
                        let path = '';
                        let tool = '';
                        for (const segment of segments) {
                            //'<path d="M 100 100 L 300 100 L 200 300 z" fill="red" stroke="blue" stroke-width="3" />'
                            if (segment['Visible'] !== 1) {
                                tool = 'M';
                            } else {
                                tool = 'L';
                            }
                            let xStart = (this.imx - ((segment['LocationStart']['X'] - this.minX) / 100 * this.scale));
                            let yStart = (this.imy - ((segment['LocationStart']['Y'] - this.minY) / 100 * this.scale));
                            let xEnd = (this.imx - ((segment['LocationEnd']['X'] - this.minX) / 100 * this.scale));
                            let yEnd = (this.imy - ((segment['LocationEnd']['Y'] - this.minY) / 100 * this.scale));
                            if (path === '') {
                                path = 'M ' + xStart + ',' + yStart + ' ';
                                path += tool + ' ' + xEnd + ',' + yEnd + ' ';
                            } else {
                                path += tool + ' ' + xEnd + ',' + yEnd + ' ';
                            }
                        }
                        bedSegment.setAttribute("d", path);
                        bedSegment.setAttribute("fill", 'none');
                        bedSegment.setAttribute("stroke", stroke);
                        bedSegment.setAttribute("stroke-width", strokeWidth.toString());
                        bedsGroup.appendChild(bedSegment);
                        if (type === 7) {
                            ironBridgeGroup.appendChild(bedSegment.cloneNode(true));
                        }
                    } else {
                        // tracks..
                        let segmentIndex = -1;
                        for (const segment of segments) {
                            segmentIndex++;
                            if (segment['Visible'] !== 1) {
                                continue
                            }

                            let vOrto2 = [];
                            let vOrto = [];
                            let xStart = (this.imx - ((segment['LocationStart']['X'] - this.minX) / 100 * this.scale));
                            let yStart = (this.imy - ((segment['LocationStart']['Y'] - this.minY) / 100 * this.scale));
                            let xEnd = (this.imx - ((segment['LocationEnd']['X'] - this.minX) / 100 * this.scale));
                            let yEnd = (this.imy - ((segment['LocationEnd']['Y'] - this.minY) / 100 * this.scale));
                            let xCenter = (this.imx - ((segment['LocationCenter']['X'] - this.minX) / 100 * this.scale));
                            let yCenter = (this.imy - ((segment['LocationCenter']['Y'] - this.minY) / 100 * this.scale));

                            if (segments[segmentIndex + 1] !== undefined) {

                                let xx = segment['LocationEnd']['X'] - segment['LocationStart']['X'];
                                let yy = segment['LocationEnd']['Y'] - segment['LocationStart']['Y'];
                                if (true) {
                                    vOrto[0] = -yy; // / ortoLength;
                                    vOrto[1] = xx; // / ortoLength;

                                    let nextSegment = segments[segmentIndex + 1];
                                    vOrto2[0] = -1 * (nextSegment['LocationEnd']['Y'] - nextSegment['LocationStart']['Y']);
                                    vOrto2[1] = nextSegment['LocationEnd']['X'] - nextSegment['LocationStart']['X'];

                                    if (true) {

                                        let O = this.checkLineIntersection(
                                            segment['LocationStart']['X'],
                                            segment['LocationStart']['Y'],
                                            segment['LocationStart']['X'] + vOrto[0],
                                            segment['LocationStart']['Y'] + vOrto[1],
                                            nextSegment['LocationEnd']['X'],
                                            nextSegment['LocationEnd']['Y'],
                                            nextSegment['LocationEnd']['X'] + vOrto2[0],
                                            nextSegment['LocationEnd']['Y'] + vOrto2[1],
                                        )

                                        if (O !== null) {
                                            let OP = {}
                                            OP.X = O.x;
                                            OP.Y = O.y;
                                            let radius = Math.round(this._dist(OP, segment['LocationStart'], true) / 100);
                                            let index = 8;
                                            if (radius < 120) {
                                                index = 7;
                                            }
                                            if (radius < 60) {
                                                index = 6;
                                            }
                                            if (radius < 40) {
                                                index = 5;
                                            }
                                            if (this._getDistanceToNearestCurveLabel([xCenter, yCenter], (index - 5)) > 20) {
                                                this.allCurveLabels[(index - 5)].push([xEnd, yEnd]);
                                                if (radius < 500) {
                                                    // console.log('OOOO: ' +OP['X']+', '+OP['Y']+' R '+radius);
                                                    let degrees = null;
                                                    if (segment['LocationEnd']['X'] === segment['LocationStart']['X']) {
                                                        degrees = 90;
                                                    }
                                                    const tanA = (
                                                        (segment['LocationEnd']['Y'] - segment['LocationStart']['Y']) /
                                                        (segment['LocationEnd']['X'] - segment['LocationStart']['X'])
                                                    );
                                                    degrees = this._rad2deg(Math.atan(tanA));
                                                    if (degrees > 0) {
                                                        degrees -= 90;
                                                    } else {
                                                        degrees += 90;
                                                    }
                                                    degrees += 180;

                                                    const radiusLabel = document.createElementNS(this.svgNS, "text");
                                                    const radiusText = document.createTextNode('__> ' + radius + 'm');
                                                    radiusLabel.setAttribute("x", Math.round(Math.round(xEnd).toString()).toString());
                                                    radiusLabel.setAttribute("y", Math.round(Math.round(yEnd).toString()).toString());
                                                    radiusLabel.setAttribute("transform",
                                                        "rotate(" + Math.round(degrees) + "," +
                                                        Math.round(xEnd) + "," +
                                                        Math.round(yEnd) + ")");
                                                    radiusLabel.appendChild(radiusText);
                                                    slopeLabelGroup[index].appendChild(radiusLabel);

                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            const trackSegment = document.createElementNS(this.svgNS, 'line');
                            trackSegment.setAttribute("x1", xStart.toString());
                            trackSegment.setAttribute("y1", yStart.toString());
                            trackSegment.setAttribute("x2", xEnd.toString());
                            trackSegment.setAttribute("y2", yEnd.toString());
                            trackSegment.setAttribute("sp", splineIndex.toString());
                            trackSegment.setAttribute("se", segmentIndex.toString());
                            trackSegment.setAttribute("stroke", stroke);
                            trackSegment.setAttribute('onclick',
                                'my_function(' + splineIndex + ',' + segmentIndex + ')');
                            trackSegment.setAttribute("stroke-width", strokeWidth.toString());
                            tracksGroup.appendChild(trackSegment);

                            let distance = Math.sqrt(
                                Math.pow(segment['LocationEnd']['X'] - segment['LocationStart']['X'], 2) +
                                Math.pow(segment['LocationEnd']['Y'] - segment['LocationStart']['Y'], 2) +
                                Math.pow(segment['LocationEnd']['Z'] - segment['LocationStart']['Z'], 2)
                            )

                            let slope = 0;
                            const height = Math.abs(segment['LocationEnd']['Z'] - segment['LocationStart']['Z']);
                            const length = Math.sqrt(
                                Math.pow(segment['LocationEnd']['X'] - segment['LocationStart']['X'], 2) +
                                Math.pow(segment['LocationEnd']['Y'] - segment['LocationStart']['Y'], 2));

                            if (length === null || length === undefined || length === 0 || length === 0.0) {
                                const emptyLengthTrack = document.createElementNS(this.svgNS, "circle");
                                emptyLengthTrack.setAttribute("cx", xCenter.toString());
                                emptyLengthTrack.setAttribute("cy", yCenter.toString());
                                emptyLengthTrack.setAttribute("r", "10");
                                emptyLengthTrack.setAttribute("stroke", "red");
                                emptyLengthTrack.setAttribute("stroke-width", "2");
                                emptyLengthTrack.setAttribute("fill", "red");
                                tracksGroup.appendChild(emptyLengthTrack);

                                continue; //This may cause issues down the road. We may need to stop at this point and return the errors segment.
                            } else {
                                slope = (height * 100 / length);
                                if (slope > this.maxSlope) {
                                    slopecoords = [xCenter, yCenter];
                                }
                                this.maxSlope = Math.max(this.maxSlope, slope);
                            }

                            const slopeTriggerDecimals = 1;
                            if (distance > 0) {
                                let degrees = null;
                                if (segment['LocationEnd']['X'] === segment['LocationStart']['X']) {
                                    degrees = 90;
                                }
                                const tanA = (
                                    (segment['LocationEnd']['Y'] - segment['LocationStart']['Y']) /
                                    (segment['LocationEnd']['X'] - segment['LocationStart']['X'])
                                );
                                degrees = this._rad2deg(Math.atan(tanA));
                                if (degrees > 0) {
                                    degrees -= 90;
                                } else {
                                    degrees += 90;
                                }

                                if (this._getDistanceToNearestLabel([xCenter, yCenter]) > 20) {

                                    let percentage = this._round(slope, slopeTriggerDecimals);
                                    let percentageSilly = this._round(slope, 6);

                                    let numberX = Math.min(3, Math.floor(slope));

                                    this.allLabels.push([xCenter, yCenter]);

                                    const slopeLabelSilly = document.createElementNS(this.svgNS, "text");
                                    const textNodeSilly = document.createTextNode(this.config.labelPrefix + percentageSilly + "%");
                                    slopeLabelSilly.setAttribute("x", Math.round(xCenter).toString());
                                    slopeLabelSilly.setAttribute("y", Math.round(yCenter).toString());
                                    slopeLabelSilly.setAttribute("transform", "rotate(" + Math.round(degrees) + "," + Math.round(xCenter) + "," + Math.round(yCenter) + ")");
                                    slopeLabelSilly.appendChild(textNodeSilly);
                                    slopeLabelGroup[4].appendChild(slopeLabelSilly);
//console.log(slopeLabelSilly);
                                    const slopeLabel = document.createElementNS(this.svgNS, "text");
                                    const textNode = document.createTextNode(this.config.labelPrefix + percentage + "%");
                                    slopeLabel.setAttribute("x", Math.round(xCenter).toString());
                                    slopeLabel.setAttribute("y", Math.round(yCenter).toString());
                                    slopeLabel.setAttribute("transform", "rotate(" + Math.round(degrees) + "," + Math.round(xCenter) + "," + Math.round(yCenter) + ")");
                                    slopeLabel.appendChild(textNode);
                                    slopeLabelGroup[numberX].appendChild(slopeLabel);
                                }
                            }
                        }
                    }
                }
            }
        }

        // }

        if (this.drawMaxSlope) {
            for (const r in [5, 4, 3, 2]) {
                const maxSlopeCircle = document.createElementNS(this.svgNS, "circle");
                maxSlopeCircle.setAttribute("cx", slopecoords[0].toString());
                maxSlopeCircle.setAttribute("cy", slopecoords[1].toString());
                maxSlopeCircle.setAttribute("r", (this.turnTableRadius * r).toString());
                maxSlopeCircle.setAttribute("stroke", "orange");
                maxSlopeCircle.setAttribute("stroke-width", "5");
                maxSlopeCircle.setAttribute("fill", "none");
                maxSlopeLabelGroup.appendChild(maxSlopeCircle);
                this.shapes.push(maxSlopeLabelGroup);
            }
        }
        this.shapes.push(bedsGroup);
        this.shapes.push(ironBridgeGroup);
        this.shapes.push(tracksGroup);
        this.shapes.push(slopeLabelGroup[0]);
        this.shapes.push(slopeLabelGroup[1]);
        this.shapes.push(slopeLabelGroup[2]);
        this.shapes.push(slopeLabelGroup[3]);
        this.shapes.push(slopeLabelGroup[4]);
        this.shapes.push(slopeLabelGroup[5]);
        this.shapes.push(slopeLabelGroup[6]);
        this.shapes.push(slopeLabelGroup[7]);
        this.shapes.push(slopeLabelGroup[8]);
    }

    getSwitches() {
        if (!('Switchs' in this.json)) {
            return
        }

        const switchesGroup = document.createElementNS(this.svgNS, "g");
        switchesGroup.setAttribute("class", "switches");

        for (const swtch of this.json['Switchs']) { // can't use 'switch' as variable name
            let dir = false;
            const type = swtch['Type'];

            /**
             * 0 = SwitchLeft           = lever left switch going left
             * 1 = SwitchRight          = lever right switch going right
             * 2 =                      = Y
             * 3 =                      = Y mirror
             * 4 = SwitchRightMirror    = lever left switch going right
             * 5 = SwitchLeftMirror     = lever right switch going left
             * 6 = SwitchCross90        = cross
             */
            let state = swtch['Side'];
            switch (type) {
                case 0:
                    dir = -6;
                    state = !state;
                    break;
                case 1:
                case 3:
                case 4:
                    dir = 6;
                    break;
                case 2:
                    dir = -6;
                    break;
                case 5:
                    state = !state;
                    dir = -6;
                    break;
                case 6:
                    dir = 99;
                    break;
                default:
                    dir = 1;
            }

            if (!dir) {
                console.log("Switch error in switch " + JSON.stringify(swtch));
            }

            const rotation = this._deg2rad(swtch['Rotation'][1] - 90);
            const rotSide = this._deg2rad(swtch['Rotation'][1] - 90 + dir);
            const rotCross = this._deg2rad(swtch['Rotation'][1] + 180);

            const x = (this.imx - ((swtch['Location'][0] - this.minX) / 100 * this.scale));
            const y = (this.imy - ((swtch['Location'][1] - this.minY) / 100 * this.scale));

            if (dir === 99) { // Cross
                const crosslength = this.switchRadius / 10;
                const x2 = (this.imx - ((swtch['Location'][0] - this.minX) / 100 * this.scale) + (Math.cos(rotCross) * crosslength));
                const y2 = (this.imy - ((swtch['Location'][1] - this.minY) / 100 * this.scale) + (Math.sin(rotCross) * crosslength));
                const cx = x + (x2 - x) / 2;
                const cy = y + (y2 - y) / 2;

                switchesGroup.appendChild(this.createSVGElement("line", {
                    x1: x,
                    y1: y,
                    x2: x2,
                    y2: y2,
                    stroke: "black",
                    'stroke-width': "3"
                }));

                switchesGroup.appendChild(this.createSVGElement("line", {
                    x1: (cx - (Math.cos(rotation) * crosslength)),
                    y1: (cy - (Math.sin(rotation) * crosslength)),
                    x2: (cx + (Math.cos(rotation) * crosslength)),
                    y2: (cy + (Math.sin(rotation) * crosslength)),
                    stroke: "black",
                    'stroke-width': "3"
                }));
            } else {
                const xStraight = (this.imx - ((swtch['Location'][0] - this.minX) / 100 * this.scale) + (Math.cos(rotation) * this.switchRadius / 2));
                const yStraight = (this.imy - ((swtch['Location'][1] - this.minY) / 100 * this.scale) + (Math.sin(rotation) * this.switchRadius / 2));
                const xSide = (this.imx - ((swtch['Location'][0] - this.minX) / 100 * this.scale) + (Math.cos(rotSide) * this.switchRadius / 2));
                const ySide = (this.imy - ((swtch['Location'][1] - this.minY) / 100 * this.scale) + (Math.sin(rotSide) * this.switchRadius / 2));

                if (state) {
                    switchesGroup.appendChild(this.createSVGElement("line", {
                        x1: x,
                        y1: y,
                        x2: xStraight,
                        y2: yStraight,
                        stroke: "red",
                        'stroke-width': "3"
                    }));

                    switchesGroup.appendChild(this.createSVGElement("line", {
                        x1: x,
                        y1: y,
                        x2: xSide,
                        y2: ySide,
                        stroke: "black",
                        'stroke-width': "3"
                    }));
                } else {
                    switchesGroup.appendChild(this.createSVGElement("line", {
                        x1: x,
                        y1: y,
                        x2: xSide,
                        y2: ySide,
                        stroke: "red",
                        'stroke-width': "3"
                    }));

                    switchesGroup.appendChild(this.createSVGElement("line", {
                        x1: x,
                        y1: y,
                        x2: xStraight,
                        y2: yStraight,
                        stroke: "black",
                        'stroke-width': "3"
                    }));
                }
            }
        }
        this.shapes.push(switchesGroup);
    }

    getTurntables() {
        if (!('Turntables' in this.json)) {
            return
        }

        const turntablesGroup = document.createElementNS(this.svgNS, "g");
        turntablesGroup.setAttribute("class", "turntables");

        for (const turntable of this.json['Turntables']) {
            /**
             * 0 = regular
             * 1 = light and nice
             */
            const rotation = this._deg2rad(turntable['Rotator'][1] + 90);
            // const rotation2 = this._deg2rad(turntable['Rotator'][1] + 90 - turntable['Deck'][1]);
            const rotation2 = rotation;
            this.turnTableRadius = 25;

            const x = (this.imx - ((turntable['Location'][0] - this.minX) / 100 * this.scale));
            const y = (this.imx - ((turntable['Location'][1] - this.minX) / 100 * this.scale));
            const x2 = (this.imx - ((turntable['Location'][0] - this.minX) / 100 * this.scale) + (Math.cos(rotation) * this.turnTableRadius));
            const y2 = (this.imy - ((turntable['Location'][1] - this.minY) / 100 * this.scale) + (Math.sin(rotation) * this.turnTableRadius));
            const cx = x + (x2 - x) / 2;
            const cy = y + (y2 - y) / 2;

            const turntableCircle = document.createElementNS(this.svgNS, "circle");
            turntableCircle.setAttribute("cx", cx.toString());
            turntableCircle.setAttribute("cy", cy.toString());
            turntableCircle.setAttribute("r", (this.turnTableRadius / 2).toString());
            turntableCircle.setAttribute("stroke", "black");
            turntableCircle.setAttribute("stroke-width", "1");
            turntableCircle.setAttribute("fill", "lightyellow");
            turntablesGroup.appendChild(turntableCircle);

            const turntableLine = document.createElementNS(this.svgNS, "line");
            turntableLine.setAttribute("x1", (cx - (Math.cos(rotation2) * this.turnTableRadius / 2)).toString());
            turntableLine.setAttribute("y1", (cy - (Math.sin(rotation2) * this.turnTableRadius / 2)).toString());
            turntableLine.setAttribute("x2", (cx + (Math.cos(rotation2) * this.turnTableRadius / 2)).toString());
            turntableLine.setAttribute("y2", (cy + (Math.sin(rotation2) * this.turnTableRadius / 2)).toString());
            turntableLine.setAttribute("stroke", "black");
            turntableLine.setAttribute("stroke-width", "3");
            turntablesGroup.appendChild(turntableLine);
        }
        this.shapes.push(turntablesGroup);
    }

    getRollingStock() {
        if (!('Frames' in this.json)) {
            return
        }

        const rollingStockGroup = document.createElementNS(this.svgNS, "g");
        rollingStockGroup.setAttribute("class", "rollingstock");

        const undergroundCartsTable = document.getElementById("undergroundCartsTable");
        const rollingStockTable = document.getElementById("rollingStockTable");
        const rollingStockTable2 = document.getElementById("rollingStockTable2");
        const possibleCargos = {
            'flatcar_logs': ['log', 'steelpipe'],
            'flatcar_stakes': ['rail', 'lumber', 'beam', 'rawiron'],
            'flatcar_hopper': ['ironore', 'coal'],
            'flatcar_cordwood': ['cordwood', 'oilbarrel'],
            'flatcar_tanker': ['crudeoil'],
            'boxcar': ['crate_tools'],
        }

        const cargoNames = {
            'log': 'Logs',
            'cordwood': 'Cordwood',
            'beam': 'Beams',
            'lumber': 'Lumber',
            'ironore': 'Iron Ore',
            'rail': 'Rails',
            'rawiron': 'Raw Iron',
            'coal': 'Coal',
            'steelpipe': 'Steel Pipes',
            'crate_tools': 'Tools',
            'crudeoil': 'Crude Oil',
            'oilbarrel': 'Oil Barrels',
        }

        /**
         * flatcar: 7,856 m   aprox 25 ft 9 inch
         boxcar: 8,2282 m aprox 26ft 8 inch
         hadcart: 2,202 m   aprox 7 ft  7 inch
         betsy: 3,912 m   aprox 12 ft 10 inch
         porter: 4,6135 m   aprox 15 ft 2 inch
         eureka: 8,0213 m  aprox 26 ft 4inch
         eurekas tender: 4,9708 m   aprox 15 ft 4 inch
         mogul: 8,3783 m  aprox 27 ft 6 inch
         mogul tender: 6,4173 m aprox 21 ft 1 inch
         class 70: 9,3890 maprox 30 ft 10 inch
         class 70 tender: 6,7881 m aprox 22 ft 3 inch
         cross - length: 3,8385 m aprox  12 ft 7 inch
         climax: 8,4989 m aprox 27 ft 11 inch
         heisler: 9,1373 m aprox 30 ft (0 inch)
         max track length: 10,5 m  aprox 34 ft 5 inch
         straight part of switch: 18,8 m aprox  61 ft 8 inch

         width of flatcar: 1,9327 m aprox 6 ft 4 inch
         * @type {number}
         */
        const cartOptions = {
            'handcar': [4.202, 'white'],
            'porter_040': [3.912, 'black'],
            'porter_042': [4.6135, 'black'],
            'eureka': [8.0213, 'black'],
            'eureka_tender': [4.9708, 'black'],
            'climax': [8.4989, 'black'],
            'heisler': [9.1373, 'black'],
            'class70': [9.3890, 'black'],
            'class70_tender': [6.7881, 'black'],
            'cooke260': [8.3783, 'black'],
            'cooke260_tender': [6.4173, 'black'],
            'flatcar_logs': [7.856, 'indianred', 'red'],
            'flatcar_cordwood': [7.856, 'orange', 'orangered'],
            'flatcar_stakes': [7.856, 'greenyellow', 'green'],
            'flatcar_hopper': [7.856, 'rosybrown', 'brown'],
            'boxcar': [8.2282, 'mediumpurple', 'purple'],
            'flatcar_tanker': [7.856, 'lightgray', 'dimgray'],
            'caboose': [6.096, 'red', 'red'],
        }

        let index = 0;
        for (const vehicle of this.json['Frames']) {
            let stroke = 'black';
            if (vehicle['Brake'] > 0.2) {
                stroke = 'orange';
            }
            if (vehicle['Brake'] > 0.5) {
                stroke = 'red';
            }
            if (vehicle['Brake'] === 0) {
                stroke = 'black';
            }
            const x = (this.imx - ((vehicle['Location'][0] - this.minX) / 100 * this.scale));
            const y = (this.imy - ((vehicle['Location'][1] - this.minY) / 100 * this.scale));
            if (['porter_040',
                'porter_042', /*'handcar', */
                'eureka',
                'climax',
                'heisler',
                'class70',
                'cooke260'].indexOf(vehicle['Type']) >= 0) {
                const yl = 1.9 * 3;
                const xl = (cartOptions[vehicle['Type']][0] - 0.6) * 2;
                const path = document.createElementNS(this.svgNS, "path");
                path.setAttribute("transform", "rotate(" + Math.round(vehicle['Rotation'][1]) + ", " + x + ", " + y + ")");
                path.setAttribute("d", "M" + (x - (xl / 2)) + "," + y + " l " + (xl / 3) + "," + (yl / 2) + " l " + (xl / 3 * 2) + ",0 l 0,-" + yl + " l -" + (xl / 3 * 2) + ",0 z");
                path.setAttribute("fill", "purple");
                path.setAttribute("stroke", stroke);
                path.setAttribute("stroke-width", "1");
                rollingStockGroup.appendChild(path);
            } else {
                // const yl = (this.engineRadius / 3) * 2;
                // let xl = this.engineRadius;
                const yl = 1.9327 * 3;
//                console.log(vehicle['Type']);
                const xl = (cartOptions[vehicle['Type']][0] - 0.6) * 2;

                // if (vehicle['Type'].toLowerCase().indexOf('tender') !== -1) {
                //     xl = xl / 3 * 2;
                // }
                const path = document.createElementNS(this.svgNS, "rect");
                let fillColor = cartOptions[vehicle['Type']][1];
                fillColor = cookies.get('ce_' + vehicle['Type']);
                if (vehicle['Type'] === 'handcar') {
                    fillColor = 'white';
                }
                path.setAttribute("class", 'ce_' + vehicle['Type']);
                if (
                    typeof vehicle['Freight'] !== 'undefined' &&
                    typeof vehicle['Freight']['Amount'] !== 'undefined' &&
                    vehicle['Freight']['Amount'] > 0 &&
                    cartOptions[vehicle['Type']][2] !== undefined
                ) {
                    path.setAttribute("class", 'cf_' + vehicle['Type']);
                    fillColor = cartOptions[vehicle['Type']][2];
                    fillColor = cookies.get('cf_' + vehicle['Type']);
                }

                const title = document.createElementNS(this.svgNS, "title");
                title.textContent = vehicle['Name'].replace(/<\/?[^>]+(>|$)/g, "") + " " + vehicle['Number'].replace(/<\/?[^>]+(>|$)/g, "");
                if (
                    typeof vehicle['Freight'] !== 'undefined' &&
                    typeof vehicle['Freight']['Amount'] !== 'undefined' &&
                    vehicle['Freight']['Amount'] === 0) {
                    title.textContent += " (empty " + vehicle['Freight']['Type'] + ")";
                } else {
                    if (typeof vehicle['Freight'] !== 'undefined' &&
                        typeof vehicle['Freight']['Type'] !== 'undefined'
                    ) {
                        title.textContent += " (" + vehicle['Freight']['Type'] + " x" + vehicle['Freight']['Amount'] + ")";
                    }
                }
                let cc, cx, cy;
                cc = this.thingy(vehicle['Rotation'][1], x, y);
                cx = cc[0];
                cy = cc[1];
                // path.setAttribute("d", "M" + x + "," + y + " m-" + (xl / 2) + ",-" + (yl / 2) + " h" + (xl - 4) + " a2,2 0 0 1 2,2 v" + (yl - 4) + " a2,2 0 0 1 -2,2 h-" + (xl - 4) + " a2,2 0 0 1 -2,-2 v-" + (yl - 4) + " a2,2 0 0 1 2,-2 z");
                const width = 1.9327;
                const length = cartOptions[vehicle['Type']][0];
                path.setAttribute("width", length * 1.8); // make car bigger
                path.setAttribute("height", width * 3);
                path.setAttribute("x", x - (path.getAttribute("width") / 2)); // pass x center point
                path.setAttribute("y", y - (path.getAttribute("height") / 2));
                path.setAttribute("rx", 1.5) // corner radius
                path.setAttribute("ry", 1.5)
                path.setAttribute("fill", fillColor);
                path.setAttribute("stroke", stroke);
                path.setAttribute("stroke-width", "1");
                path.setAttribute("transform", "rotate(" + Math.round(vehicle['Rotation'][1]) + ", " + x + ", " + y + ")");
                path.setAttribute("fill", fillColor);
                // path.setAttribute("transform", "rotate(" + vehicle['Rotation'][1] + ", " + (cx-1) + ", " + cy + ")");
                path.appendChild(title);
                rollingStockGroup.appendChild(path);
            }

            if (vehicle['Location'][2] < 1000) { // Assuming this checks for vehicles under ground, rename sunkenVehicle if otherwise.
                const sunkenVehicle = document.createElementNS(this.svgNS, "ellipse");
                sunkenVehicle.setAttribute("cx", x.toString());
                sunkenVehicle.setAttribute("cy", y.toString());
                sunkenVehicle.setAttribute("rx", ((this.engineRadius / 2) * 10).toString());
                sunkenVehicle.setAttribute("ry", ((this.engineRadius / 2) * 10).toString());
                sunkenVehicle.setAttribute("style", "fill:none;stroke:red;stroke-width:10");
                sunkenVehicle.setAttribute("transform", "rotate(" + vehicle['Rotation'][1] + ", " + (this.imx - ((vehicle['Location'][0] - this.minX) / 100 * this.scale)) + ", " + (this.imy - ((vehicle['Location'][1] - this.minY) / 100 * this.scale)) + ")");
                rollingStockGroup.appendChild(sunkenVehicle);

                const sunkenVehicleLabel = document.createElementNS(this.svgNS, "text");
                const textNode = document.createTextNode("&nbsp;&nbsp;" + vehicle['Location'][2]);
                sunkenVehicleLabel.setAttribute("x", x.toString());
                sunkenVehicleLabel.setAttribute("y", y.toString());
                sunkenVehicleLabel.appendChild(textNode);
                rollingStockGroup.appendChild(sunkenVehicleLabel);

                const cartInput = document.createElement("input");
                cartInput.name = "underground_" + index;
                cartInput.value = "1";
                cartInput.type = "hidden";
                undergroundCartsTable.appendChild(cartInput);
            }

            if (['porter_040',
                'porter_042', /*'handcar', */
                'eureka',
                'climax',
                'heisler',
                'class70',
                'cooke260'].indexOf(vehicle['Type']) >= 0) {
                let name = vehicle['Name'].replace(/(<([^>]+)>)/gi, "").toUpperCase();
                if (!name) {
                    name = this._capitalize(vehicle['Type']);
                }

                let textRotation = vehicle['Rotation'][1];
                if (textRotation < 0) {
                    textRotation += 90;
                } else {
                    textRotation -= 90;
                }
                this.allLabels.push([x, y]);

                const vehicleLabel = document.createElementNS(this.svgNS, "text");
                const textNode = document.createTextNode(this.config.labelPrefix + name);
                vehicleLabel.setAttribute("stroke", "black");
                vehicleLabel.setAttribute("fill", "white");
                vehicleLabel.setAttribute("font-size", "1em");
                vehicleLabel.setAttribute("x", x.toString());
                vehicleLabel.setAttribute("y", y.toString());
                vehicleLabel.setAttribute("transform", "rotate(" + textRotation + ", " + x + ", " + y + ")")
                vehicleLabel.appendChild(textNode);
                rollingStockGroup.appendChild(vehicleLabel);
            }

            let cargo = "firewood";
            let amount;
            let amountString = "tenderamount_";
            if (vehicle['Type'] in possibleCargos) {
                cargo = possibleCargos[vehicle['Type']];
                amount = vehicle['Freight']['Amount'];
                amountString = "freightamount_";
            } else {
                amount = vehicle['Tender']['Fuelamount'];
            }

            const rollingStockInfoRow = document.createElement("tr");
            /*
                  var a = document.createElement('a');
                  var linkText = document.createTextNode("my title text");
                  a.appendChild(linkText);
                  a.title = "my title text";
                  a.href = "http://example.com";
                  document.body.appendChild(a);

             */
            const typeValue = document.createElement("td");
            const a = document.createElement('a');
            const typeImage = document.createElement("img");
            typeImage.src = "/assets/images/" + vehicle['Type'] + ".png";
            a.appendChild(typeImage);
            // (((400000-($task[1]['x']+200000))/400000))
            a.href = "javascript:zoomTo(" + (400000 - (vehicle['Location'][0] + 200000)) / 400000 + "," + (400000 - (vehicle['Location'][1] + 200000)) / 400000 + ")";
            typeValue.appendChild(a);
            rollingStockInfoRow.appendChild(typeValue);

            const nameValue = document.createElement("td");
            const nameTextInput = document.createElement("input");
            nameTextInput.size = 5;
            nameTextInput.maxLength = 22;  // locos have 22 - carts 15
            nameTextInput.name = "name_" + index;
            nameTextInput.value = vehicle['Name'].replace(/<\/?[^>]+(>|$)/g, "");
            nameValue.appendChild(nameTextInput);
            rollingStockInfoRow.appendChild(nameValue);

            const numberValue = document.createElement("td");
            const numberTextInput = document.createElement("input");
            numberTextInput.size = 5;
            numberTextInput.maxLength = 4;
            numberTextInput.name = "number_" + index;
            numberTextInput.value = vehicle['Number'].replace(/<\/?[^>]+(>|$)/g, "");
            numberValue.appendChild(numberTextInput);
            rollingStockInfoRow.appendChild(numberValue);

            const nearValue = document.createElement("td");
            let nearTextNode = document.createTextNode("Unknown");
            if ('Industries' in this.json) {
                nearTextNode = document.createTextNode(this._nearestIndustry(vehicle['Location'], this.json['Industries']));
            }
            nearValue.appendChild(nearTextNode);
            rollingStockInfoRow.appendChild(nearValue);

            const cargoValue = document.createElement("td");
            if (typeof cargo === "object") {
                const select = document.createElement("select");
                select.name = "cargoType_" + index;
                for (const cargoType of cargo) {
                    const option = document.createElement("option");
                    option.text = cargoNames[cargoType];
                    option.value = cargoType;
                    option.selected = vehicle['Freight']['Type'] === cargoType;
                    select.add(option);
                }
                cargoValue.appendChild(select);
            } else {
                const cargoTextNode = document.createTextNode(cargo);
                cargoValue.appendChild(cargoTextNode);
            }
            rollingStockInfoRow.appendChild(cargoValue);

            const amountValue = document.createElement("td");
            const amountTextInput = document.createElement("input");
            amountTextInput.name = amountString + index;
            amountTextInput.value = amount;
            amountTextInput.size = 2;
            amountTextInput.maxLength = 4;
            amountValue.appendChild(amountTextInput);
            rollingStockInfoRow.appendChild(amountValue);
            if (['porter_040',
                'porter_042',
                /*'handcar',*/
                'eureka',
                'eureka_tender',
                'climax',
                'heisler',
                'class70',
                'class70_tender',
                'cooke260',
                'cooke260_tender'
            ].indexOf(vehicle['Type']) >= 0) {
                rollingStockTable.appendChild(rollingStockInfoRow);
            } else {
                rollingStockTable2.appendChild(rollingStockInfoRow);
            }

            index++;
        }
        this.shapes.push(rollingStockGroup);
    }

    getIndustries(industryHtml) {
        if (!('Industries' in this.json)) {
            return
        }
        let x;
        let y;
        let path;
        const industryLabelGroup = document.createElementNS(this.svgNS, "g");
        industryLabelGroup.setAttribute("class", "industryLabel");

        const industriesTable = document.getElementById("industriesTable");
        industryLabelGroup.insertAdjacentHTML("beforeend", industryHtml);
        let index = 0;
        for (const industry of this.json['Industries']) {
            let name = '';
            let rotation = 0;
            let xoff = 0;
            let yoff = 0;
            let pis = [];
            let pos = [];
            let indLength;
            let indWidth;
            x = (this.imx - ((industry['Location'][0] - this.minX) / 100 * this.scale));
            y = (this.imy - ((industry['Location'][1] - this.minY) / 100 * this.scale));

            switch (industry['Type']) {
                case 1:
                    industry['EductsStored'].pop();
                    industry['EductsStored'].pop();
                    industry['EductsStored'].pop();
                    industry['EductsStored'].pop();
                    pos = ['logs_p.svg', 'cordwood_p.svg', 'cordwood_p.svg', 'logs_p.svg'];
                    name = "Logging Camp";
                    rotation = 0;
                    xoff = -70;
                    yoff = -30;

                    break;
                case 2:
                    name = 'Sawmill';
                    industry['EductsStored'].pop();
                    industry['EductsStored'].pop();
                    industry['EductsStored'].pop();
                    industry['ProductsStored'].pop();
                    industry['ProductsStored'].pop();
                    pos = ['lumber_p.svg', 'beams_p.svg'];
                    pis = ['logs_p.svg'];
                    xoff = -35;
                    yoff = -15;
                    rotation = 45;
                    break;
                case 3:
                    name = 'Smelter';
                    industry['EductsStored'].pop();
                    industry['EductsStored'].pop();
                    industry['ProductsStored'].pop();
                    industry['ProductsStored'].pop();
                    pos = ['iron_p.svg', 'rails_p.svg'];
                    pis = ['cordwood_p.svg', 'ironore_p.svg'];
                    rotation = 90;
                    break;
                case 4:
                    name = 'Ironworks';
                    industry['EductsStored'].pop();
                    industry['EductsStored'].pop();
                    industry['ProductsStored'].pop();
                    industry['ProductsStored'].pop();
                    pos = ['pipes_p.svg', 'tools_p.svg'];
                    pis = ['iron_p.svg', 'coal_p.svg'];
                    rotation = industry['Rotation'][1];
                    xoff = -50;
                    break;
                case 5:
                    name = 'Oilfield';
                    industry['EductsStored'].pop();
                    industry['ProductsStored'].pop();
                    industry['ProductsStored'].pop();
                    industry['ProductsStored'].pop();
                    pis = ['pipes_p.svg', 'beams_p.svg', 'tools_p.svg'];
                    pos = ['oil_p.svg'];
                    rotation = 0;
                    break;
                case 6:
                    name = 'Refinery';
                    industry['EductsStored'].pop();
                    industry['ProductsStored'].pop();
                    industry['ProductsStored'].pop();
                    pis = ['oil_p.svg', 'pipes_p.svg', 'lumber_p.svg'];
                    pos = ['barrels_p.svg', 'barrels_p.svg'];
                    rotation = 0;
                    break;
                case 7:
                    name = 'Coal Mine';
                    industry['EductsStored'].pop();
                    industry['EductsStored'].pop();
                    industry['ProductsStored'].pop();
                    industry['ProductsStored'].pop();
                    industry['ProductsStored'].pop();
                    pis = ['beams_p.svg', 'rails_p.svg'];
                    pos = ['coal_p.svg'];
                    rotation = industry['Rotation'][1] - 90;
                    xoff = -60;
                    yoff = -30;
                    break;
                case 8:
                    name = 'Iron Mine';
                    industry['EductsStored'].pop();
                    industry['EductsStored'].pop();
                    industry['ProductsStored'].pop();
                    industry['ProductsStored'].pop();
                    industry['ProductsStored'].pop();
                    pis = ['lumber_p.svg', 'beams_p.svg'];
                    pos = ['ironore_p.svg'];
                    rotation = industry['Rotation'][1] + 90;
                    yoff = +20;
                    xoff = -90;
                    break;
                case 9:
                    name = 'Freight Depot';
                    industry['EductsStored'].pop();
                    industry['EductsStored'].pop();
                    industry['EductsStored'].pop();
                    industry['EductsStored'].pop();
                    industry['ProductsStored'].pop();
                    industry['ProductsStored'].pop();
                    industry['ProductsStored'].pop();
                    industry['ProductsStored'].pop();
                    rotation = industry['Rotation'][1];
                    xoff = -50;
                    rotation = industry['Rotation'][1];
                    break;
                case 10:
                    industry['EductsStored'].pop();
                    industry['EductsStored'].pop();
                    industry['EductsStored'].pop();
                    pis = ['cordwood_p.svg'];
                    name = 'F#' + index;
                    rotation = industry['Rotation'][1] + 90;
                    path = document.createElementNS(this.svgNS, "path");
                    path.setAttribute("transform", "rotate(" + Math.round(industry['Rotation'][1]) + ", " + x + ", " + y + ")");
                    path.setAttribute("d", "M" + x + "," + y + " m-18,-15 l10,0 l0,30 l-10,0 z");
                    path.setAttribute("fill", "orange");
                    path.setAttribute("stroke", "brown");
                    industryLabelGroup.appendChild(path);

                    xoff = -20;
                    yoff = +18;
                    break;
                case 11:
                case 12:
                case 13:
                case 14:
                    let fill = [];
                    fill[11] = 'lightblue';
                    fill[12] = 'gold';
                    fill[13] = 'red';
                    fill[14] = 'brown';
                    industry['ProductsStored'].pop();
                    industry['ProductsStored'].pop();
                    industry['ProductsStored'].pop();
                    industry['ProductsStored'].pop();
                    industry['EductsStored'].pop();
                    industry['EductsStored'].pop();
                    industry['EductsStored'].pop();
                    industry['EductsStored'].pop();
                    name = 'H#' + index;
                    rotation = industry['Rotation'][1];
                    path = document.createElementNS(this.svgNS, "path");
                    path.setAttribute("transform", "rotate(" + Math.round(industry['Rotation'][1]) + ", " + x + ", " + y + ")");
                    path.setAttribute("d", "M" + x + "," + y + " m-45,-12 l45,0 l0,24 l-45,0 z");
                    path.setAttribute("fill", fill[industry['Type']]);
                    path.setAttribute("stroke", "brown");
                    industryLabelGroup.appendChild(path);


                    xoff = -45;
                    yoff = +6;
                    break;
                default:
                    console.log("Unknown industry: " + JSON.stringify(industry));
            }

            const industryLabel = document.createElementNS(this.svgNS, "text");
            const textNode = document.createTextNode(name);
            industryLabel.setAttribute("x", x + xoff);
            industryLabel.setAttribute("y", y + yoff);
            industryLabel.setAttribute("transform", "rotate(" + rotation + ", " + x + ", " + y + ")");
            industryLabel.appendChild(textNode);
            industryLabelGroup.appendChild(industryLabel);

            let c = document.createElementNS(this.svgNS, "circle");
            c.setAttribute("cx", x);
            c.setAttribute("cy", y);
            c.setAttribute("r", "2");
            c.setAttribute("stroke", "red");
            c.setAttribute("stroke-width", "1");
            c.setAttribute("fill", "none");
            industryLabelGroup.appendChild(c);
            // let c2= document.createElementNS(this.svgNS, "circle");
            // c2.setAttribute("cx", x+xoff);
            // c2.setAttribute("cy", y+yoff);
            // c2.setAttribute("r", "2");
            // c2.setAttribute("stroke", "green");
            // c2.setAttribute("stroke-width", "1");
            // c2.setAttribute("fill", "none");
            // industryLabelGroup.appendChild(c2);

            const eductRow = document.createElement("tr");
            eductRow.setAttribute("class", "export__educts");
            const eductnameColumn = document.createElement("td");
            const eductnameColumnText = document.createTextNode(name + " Input");
            eductnameColumn.appendChild(eductnameColumnText);
            eductRow.appendChild(eductnameColumn);
            for (let i = 0; i < industry['EductsStored'].length; i++) {
                const itemColumn = document.createElement("td");
                const itemInput = document.createElement("input");
                itemInput.size = 5;
                itemInput.maxLength = 15;
                itemInput.name = "educt" + i + "_" + index;
                itemInput.value = industry['EductsStored'][i];

                if (typeof pis[i] !== "undefined") {
                    const itemImage = document.createElement("img");
                    itemImage.setAttribute("style", "float:right");
                    itemImage.setAttribute("src", "/assets/images/" + pis[i]);
                    itemImage.setAttribute("height", "30");
                    itemColumn.appendChild(itemImage);
                }

                itemColumn.appendChild(itemInput);
                eductRow.appendChild(itemColumn);
            }
            industriesTable.appendChild(eductRow);

            const productRow = document.createElement("tr");
            productRow.setAttribute("class", "export__products");
            const productNameColumn = document.createElement("td");
            const productNameColumnText = document.createTextNode(name + " Output");
            productNameColumn.appendChild(productNameColumnText);
            productRow.appendChild(productNameColumn);
            for (let i = 0; i < industry['ProductsStored'].length; i++) {
                const itemColumn = document.createElement("td");
                const itemInput = document.createElement("input");
                itemInput.size = 5;
                itemInput.maxLength = 15;
                itemInput.name = "product" + i + "_" + index;
                itemInput.value = industry['ProductsStored'][i];

                if (typeof pos[i] !== "undefined") {
                    const itemImage = document.createElement("img");
                    itemImage.setAttribute("style", "float:right");
                    itemImage.setAttribute("src", "/assets/images/" + pos[i]);
                    itemImage.setAttribute("height", "30");
                    itemColumn.appendChild(itemImage);
                }

                itemColumn.appendChild(itemInput);
                productRow.appendChild(itemColumn);
            }
            industriesTable.appendChild(productRow);

            index += 1;
        }
        if (('Watertowers' in this.json)) {

            for (const tower of this.json['Watertowers']) {
                const x = this.imx - ((tower['Location'][0] - this.minX) / 100 * this.scale);
                const y = this.imy - ((tower['Location'][1] - this.minY) / 100 * this.scale);

                const waterTower = document.createElementNS(this.svgNS, "path");
                waterTower.setAttribute("transform", "rotate(" + Math.round(tower['Rotation'][1]) + ", " + x + ", " + y + ")");
                waterTower.setAttribute("d", "M" + x + "," + y + " m -5,-5 l10,0 l0,3 l3,0 l0,4 l-3,0 l0,3 l-10,0 z");
                waterTower.setAttribute("fill", "lightblue");
                waterTower.setAttribute("stroke", "black");
                waterTower.setAttribute("stroke-width", "1");
                industryLabelGroup.appendChild(waterTower);

                const waterTowerCircle = document.createElementNS(this.svgNS, "circle");
                waterTowerCircle.setAttribute("cx", x.toString());
                waterTowerCircle.setAttribute("cy", y.toString());
                waterTowerCircle.setAttribute("r", "3");
                waterTowerCircle.setAttribute("fill", "blue");
                industryLabelGroup.appendChild(waterTowerCircle);
            }
        }

        this.shapes.push(industryLabelGroup);
    }

    getWaterTowers() {
        // handled in industries
    }

    getZDistanceToNearestTrack(segment) {
        const treeX = Math.floor((200000 + segment['LocationCenter']['X']) / 100000);
        const treeY = Math.floor((200000 + segment['LocationCenter']['Y']) / 100000);
        const tree = [segment['LocationCenter']['X'], segment['LocationCenter']['Y'], segment['LocationCenter']['Z']];

        let minDistanceToSomething = undefined;
        try {
            for (const spline of this.json['Splines']) {
                if (spline['Type'] !== 0 && spline['Type'] !== 4) continue;

                for (const segment of spline['Segments']) {
                    if (segment['CX'].indexOf(treeX) === -1) continue;
                    if (segment['CY'].indexOf(treeY) === -1) continue;
                    if (segment['LocationCenter']['X'] < tree[0] - 6000) {
                        continue;
                    }
                    if (segment['LocationCenter']['X'] > tree[0] + 6000) {
                        continue;
                    }
                    if (segment['LocationCenter']['Y'] < tree[1] - 6000) {
                        continue;
                    }
                    if (segment['LocationCenter']['Y'] > tree[1] + 6000) {
                        continue;
                    }
                    if (minDistanceToSomething === undefined) {
                        minDistanceToSomething = tree[2] - segment['LocationCenter']['Z'];
                    }
                    minDistanceToSomething =
                        Math.min(minDistanceToSomething,
                            tree[2] - segment['LocationCenter']['Z']
                        );
                }
            }
        } catch
            (err) {
        }
        if (minDistanceToSomething === undefined) return -1;
        return minDistanceToSomething;
    }

    getReplantableTrees() {
        if (!('Removed' in this.json)) {
            return
        }

        const firstTreeGroup = document.createElementNS(this.svgNS, "g");
        firstTreeGroup.setAttribute("class", "trees_default");
        const userTreeGroup = document.createElementNS(this.svgNS, "g");
        userTreeGroup.setAttribute("class", "trees_user");

        for (let i = 0; i < this.json['Removed']['Vegetation'].length; i++) {
            const tree = this.json['Removed']['Vegetation'][i];
            const treeX = Math.floor((200000 + tree[0]) / 100000);
            const treeY = Math.floor((200000 + tree[1]) / 100000);

            let minDistanceToSomething = 80000000;
            try {
                for (const spline of this.json['Splines']) {
                    if (spline['Type'] !== 0 && spline['Type'] !== 4) continue;

                    for (const segment of spline['Segments']) {
                        if (segment['CX'].indexOf(treeX) === -1) continue;
                        if (segment['CY'].indexOf(treeY) === -1) continue;
                        if (segment['LocationCenter']['X'] < tree[0] - 6000) {
                            continue;
                        }
                        if (segment['LocationCenter']['X'] > tree[0] + 6000) {
                            continue;
                        }
                        if (segment['LocationCenter']['Y'] < tree[1] - 6000) {
                            continue;
                        }
                        if (segment['LocationCenter']['Y'] > tree[1] + 6000) {
                            continue;
                        }
                        minDistanceToSomething =
                            Math.min(minDistanceToSomething,
                                this._dist(tree, segment['LocationCenter'], true),
                                this._dist(tree, segment['LocationStart'], true),
                                this._dist(tree, segment['LocationEnd'], true)
                            );
                    }
                }
            } catch
                (err) {
            }
            if (minDistanceToSomething > cookies.get('treeMap7')) {
                const x = (this.imx - ((tree[0] - this.minX) / 100 * this.scale));
                const y = (this.imy - ((tree[1] - this.minY) / 100 * this.scale));
                const treeCircle = document.createElementNS(this.svgNS, "circle");
                treeCircle.setAttribute("cx", x.toString());
                treeCircle.setAttribute("cy", y.toString());
                treeCircle.setAttribute("r", "6");
                treeCircle.setAttribute("stroke", "darkgreen");
                treeCircle.setAttribute("stroke-width", "2");
                if (cookies.get('treeMap90') > this._nearestIndustryDistance(tree, this.json['Industries'])) {
                    treeCircle.setAttribute("fill", "green");
                    firstTreeGroup.appendChild(treeCircle);
                } else {
                    treeCircle.setAttribute("fill", "green");
                    userTreeGroup.appendChild(treeCircle);
                }
            }
        }

        this.shapes.push(firstTreeGroup);
        this.shapes.push(userTreeGroup);
    }

    _getDistanceToNearestLabel(newLabel) {
        let minDistance = 8000;
        for (const oldLabel of this.allLabels) {
            minDistance = Math.min(
                minDistance,
                Math.sqrt(
                    Math.pow(newLabel[0] - oldLabel[0], 2) +
                    Math.pow(newLabel[1] - oldLabel[1], 2)
                ));
        }

        return minDistance;
    }

    _getDistanceToNearestCurveLabel(newLabel, index) {
        let minDistance = 8000;
        for (const oldLabel of this.allCurveLabels[index]) {
            minDistance = Math.min(
                minDistance,
                Math.sqrt(
                    Math.pow(newLabel[0] - oldLabel[0], 2) +
                    Math.pow(newLabel[1] - oldLabel[1], 2)
                ));
        }

        return minDistance;
    }

    _round(value, decimals) {
        // noinspection JSCheckFunctionSignatures
        return Number(Math.round(value + 'e' + decimals) + 'e-' + decimals);
    }

    _rad2deg(radians) {
        return (radians * (180 / Math.PI));
    }

    _deg2rad(degrees) {
        return degrees * (Math.PI / 180);
    }

    _capitalize(string) {
        return String(string).charAt(0).toUpperCase() + string.slice(1).toLowerCase();
    }

    _nearestIndustryDistance(coords, industryCoords) {
        let minDist = 800000;
        for (const i of industryCoords) {
            if (i['Type'] < 10) {
                const d = this._dist(i['Location'], coords, true);
                if (d < minDist) {
                    minDist = d;
                }
            }
        }
        const d = this._dist([-5000, -5000], coords, true);
        if (d < 10000) {
            return 0;
        }

        return minDist;
    }

    _nearestIndustry(coords, industryCoords, log = false) {
        let minDist = 800000;
        let ind = 0;
        for (const i of industryCoords) {
            if (i['Type'] < 10) {
                let d = this._dist(i['Location'], coords, true);
                if (log) {
                    // console.log(d);
                    // console.log(coords);
                    // console.log(i['Location']);
                }
                if (d < minDist) {
                    minDist = d;
                    ind = i['Type'];
                }
            }
        }

        switch (ind) {
            case 1:
                name = 'Logging Camp';
                break;
            case 2:
                name = 'Sawmill';
                break;
            case 3:
                name = 'Smelter';
                break;
            case 4:
                name = 'Ironworks';
                break;
            case 5:
                name = 'Oilfield';
                break;
            case 6:
                name = 'Refinery';
                break;
            case 7:
                name = 'Coal Mine';
                break;
            case 8:
                name = 'Iron Mine';
                break;
            case 9:
                name = 'Freight Depot';
                break;
        }

        return name;
    }

    _dist(coordsIn, coords2In, flat = false) {
        if (coords2In === undefined) {
            return 9999999999;
        }

        const coords = JSON.parse(JSON.stringify(coordsIn));
        const coords2 = JSON.parse(JSON.stringify(coords2In));

        let distance;
        if ('X' in coords) {
            if ('X' in coords2) {
                if (flat === true) {
                    coords['Z'] = coords2['Z'] = 0;
                }
                distance = Math.sqrt(
                    Math.pow(coords['X'] - coords2['X'], 2) +
                    Math.pow(coords['Y'] - coords2['Y'], 2) +
                    Math.pow(coords['Z'] - coords2['Z'], 2)
                );
            } else {
                if (flat === true) {
                    coords['Z'] = coords2[2] = 0;
                }
                distance = Math.sqrt(
                    Math.pow(coords['X'] - coords2[0], 2) +
                    Math.pow(coords['Y'] - coords2[1], 2) +
                    Math.pow(coords['Z'] - coords2[2], 2)
                );
            }
        } else {
            if ('X' in coords2) {
                if (flat === true) {
                    coords[2] = coords2['Z'] = 0;
                }
                distance = Math.sqrt(
                    Math.pow(coords[0] - coords2['X'], 2) +
                    Math.pow(coords[1] - coords2['Y'], 2) +
                    Math.pow(coords[2] - coords2['Z'], 2)
                );
            } else {
                if (flat === true) {
                    coords[2] = coords2[2] = 0;
                }
                distance = Math.sqrt(
                    Math.pow(coords[0] - coords2[0], 2) +
                    Math.pow(coords[1] - coords2[1], 2) +
                    Math.pow(coords[2] - coords2[2], 2)
                );
            }
        }

        return distance;
    }

    checkLineIntersection(line1StartX, line1StartY, line1EndX, line1EndY, line2StartX, line2StartY, line2EndX, line2EndY) {
        // if the lines intersect, the result contains the x and y of the intersection (treating the lines as infinite) and booleans for whether line segment 1 or line segment 2 contain the point
        var denominator, a, b, numerator1, numerator2, result = {
            x: null,
            y: null,
            onLine1: false,
            onLine2: false
        };
        denominator = ((line2EndY - line2StartY) * (line1EndX - line1StartX)) - ((line2EndX - line2StartX) * (line1EndY - line1StartY));
        if (denominator === 0) {
            return result;
        }
        a = line1StartY - line2StartY;
        b = line1StartX - line2StartX;
        numerator1 = ((line2EndX - line2StartX) * a) - ((line2EndY - line2StartY) * b);
        numerator2 = ((line1EndX - line1StartX) * a) - ((line1EndY - line1StartY) * b);
        a = numerator1 / denominator;
        b = numerator2 / denominator;

        // if we cast these lines infinitely in both directions, they intersect here:
        result.x = line1StartX + (a * (line1EndX - line1StartX));
        result.y = line1StartY + (a * (line1EndY - line1StartY));
        /*
                // it is worth noting that this should be the same as:
                x = line2StartX + (b * (line2EndX - line2StartX));
                y = line2StartX + (b * (line2EndY - line2StartY));
                */
        // if line1 is a segment and line2 is infinite, they intersect if:
        if (a > 0 && a < 1) {
            result.onLine1 = true;
        }
        // if line2 is a segment and line1 is infinite, they intersect if:
        if (b > 0 && b < 1) {
            result.onLine2 = true;
        }
        // if line1 and line2 are segments, they intersect if both of the above are true
        return result;
    };

    polarToCartesian(centerX, centerY, radius, angleInDegrees) {
        var angleInRadians = (angleInDegrees - 90) * Math.PI / 180.0;

        return {
            x: centerX + (radius * Math.cos(angleInRadians)),
            y: centerY + (radius * Math.sin(angleInRadians))
        };
    }
}
