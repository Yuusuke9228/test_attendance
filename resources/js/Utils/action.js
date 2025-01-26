import axios from "axios";
import moment from 'moment'

export const getParams = (url, param) => {
    if (url && param) {
        const urlParams = new URLSearchParams(new URL(url).search);
        return urlParams.get(param);
    }
};

export const mime = {
    excel: [
        "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        "application/vnd.ms-excel",
        "text/csv",
    ],
    csv: "text/csv",
    img: "image",
};

export const formatedFileSize = (bytes) => {
    if (bytes >= 1073741824) {
        bytes = (bytes / 1073741824).toFixed(2) + " GB";
    } else if (bytes >= 1048576) {
        bytes = (bytes / 1048576).toFixed(2) + " MB";
    } else if (bytes >= 1024) {
        bytes = (bytes / 1024).toFixed(2) + " KB";
    } else if (bytes > 1) {
        bytes = bytes + " bytes";
    } else if (bytes == 1) {
        bytes = bytes + " byte";
    } else {
        bytes = "0 bytes";
    }
    return bytes;
};

export const waitForGeolocationPermission = (callback) => {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
             async function (position) {
                let latitude = position.coords.latitude;
                let longitude = position.coords.longitude;
                // console.log("Latitude: " + latitude + ", Longitude: " + longitude);
                let nominatimUrl = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}`;
                const res = await axios.get(nominatimUrl);
                if (res?.data) {
                    let address = "";
                    if (res.data.address?.postcode) {
                        address = "〒" + res.data.address?.postcode + " ";
                    }
                    if (res.data.address?.province) {
                        address += res.data.address?.province;
                    }
                    if (res.data.address?.city) {
                        address += res.data.address?.city;
                    }
                    if (res.data.address?.neighbourhood) {
                        address += res.data.address?.neighbourhood;
                    }
                    if(callback) callback(address);
                }
            },
            async function (error) {
                if (error.code === error.PERMISSION_DENIED) {
                    console.log(
                        "Geolocation permission denied. Waiting for permission..."
                    );
                        return "位置情報の使用が許可されていません。";
                    setTimeout(waitForGeolocationPermission, 1000); // 1秒ごとに再試行
                }
            }
        );
    } else {
        console.log("Geolocation is not supported by this browser.");
    }
};

export async function getAddress(zipCode) {
    let zipCodePattern = /(^\d{3}-\d{4})|^\d{7}$/;

    if (zipCodePattern.test(zipCode)) {
        let prefecture = null;
        let area = null;
        let address = null;
        const endPoint =
            "https://zipcloud.ibsnet.co.jp/api/search?zipcode=" + zipCode;
        await fetch(endPoint)
            .then((res) => res.json())
            .then((data) => {
                status = data.status;
                prefecture = data.results[0].address1;
                area = data.results[0].address2;
                address = data.results[0].address3;
            })
            .catch((error) => {
                console.log(error);
            });
        return {prefecture, area, address}
    }
}

export const zipcodeType = async (e) => {
    if (
        (e.key >= "0" && e.key <= "9") || // Allow numbers
        e.key === "-" || // Allow hyphen
        e.key === "Backspace" || // Allow backspace
        e.key === "ArrowLeft" || // Allow arrow keys for navigation
        e.key === "ArrowRight" || // Allow arrow keys for navigation
        e.key === "Delete" || // Allow delete key
        e.key === "Tab" // Allow tab key for tabbing between fields
    ) {
        // Allow the keypress
        return true;
    } else {
        // Prevent the default action for invalid keypresses
        e.preventDefault();
    }
}

export const master = async (table=null) => {
    const result = await axios.get(route('common.all.data', {table}));
    return result;
}

export function getWeekdays() {
    return [
        { label: "⽇", value: 0 },
        { label: "⽉", value: 1 },
        { label: "⽕", value: 2 },
        { label: "⽔", value: 3 },
        { label: "⽊", value: 4 },
        { label: "⾦", value: 5 },
        { label: "⼟", value: 6 },
    ];
}
export function calendarObject(date, startDay) {
    const startDate_ = new Date(date);
    // Create an array of day names
    const dayNames = getWeekdays();
    // create date array that include days from 1 ~ 365
    const daysList = [];
    // Create the day name cells
    for (let i = 0; i < 7; i++) {
        let dayCell = dayNames[i];
        daysList.push(dayCell)
    }
    // re assign the daysList order by weekday setting
    let sw;
    if(startDay !== 7) {
      sw = startDay
    } else {
      sw = moment(new Date(date)).format('d')
    }
    for(let weekHeIndex = 0; weekHeIndex<sw; weekHeIndex++) {
      daysList.shift()
      daysList.push(dayNames[weekHeIndex])
    }

    // Create the table body rows
    let yearList = [];
    for(let j=0; j<12; j++) {
      let monthList = [];
      let start = startDate_;
      for(let i=0; i<42; i++) {
        if(moment(start).add(j, 'month').add(i, 'days').isBefore(moment(start).add(j+1, 'month'))) {
          monthList.push(moment(start).add(j, 'month').add(i, 'days').format('yyyy/MM/DD'))
        } else {
          monthList.push("")
        }
      }
      let startDayOfMonth = moment(new Date(monthList[0])).format('d');

      // past month date remove
      let weekDiff = startDayOfMonth-sw;
      if(weekDiff >= 0) {
        for(let weIndex = 0; weIndex<weekDiff; weIndex++) {
          monthList.unshift("")
          monthList.pop()
        }
      } else {
        for(let weIndex = 0; weIndex<7 + weekDiff; weIndex++) {
          monthList.unshift("")
          monthList.pop()
        }
      }
      const dateList = []
      while(monthList.length > 0) {
        dateList.push(monthList.splice(0, 7))
      }
      yearList.push(dateList)
    }
    return {daysList: daysList, dateList: yearList};
}
export const delay = async (ms) => {
    return new Promise((resolve) => setTimeout(resolve, ms));
}