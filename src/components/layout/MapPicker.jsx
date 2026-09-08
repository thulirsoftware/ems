import { GoogleMap, Marker, Autocomplete } from "@react-google-maps/api";
import { useRef, useState } from "react";

const containerStyle = {
    width: "100%",
    height: "350px",
};

const defaultCenter = {
    lat: 13.0827,   // Chennai default
    lng: 80.2707,
};

export default function MapPicker({ lat, lng, onChange }) {
    const [position, setPosition] = useState(
        lat && lng ? { lat: Number(lat), lng: Number(lng) } : defaultCenter
    );

    const autocompleteRef = useRef(null);

    const onPlaceChanged = () => {
        const place = autocompleteRef.current.getPlace();
        if (!place?.geometry) return;

        const location = place.geometry.location;
        const newPos = {
            lat: location.lat(),
            lng: location.lng(),
        };

        setPosition(newPos);
        onChange(newPos.lat, newPos.lng);
    };

    const onMarkerDragEnd = (e) => {
        const newPos = {
            lat: e.latLng.lat(),
            lng: e.latLng.lng(),
        };

        setPosition(newPos);
        onChange(newPos.lat, newPos.lng);
    };

    return (
        <>
            {/* Search Box */}
            <Autocomplete
                onLoad={(ref) => (autocompleteRef.current = ref)}
                onPlaceChanged={onPlaceChanged}
            >
                <input
                    type="text"
                    placeholder="Search location"
                    className="w-full border rounded-md px-3 py-2 mb-2"
                />
            </Autocomplete>

            {/* Map */}
            <GoogleMap
                mapContainerStyle={containerStyle}
                center={position}
                zoom={14}
                onClick={(e) => onMarkerDragEnd(e)}
            >
                <Marker
                    position={position}
                    draggable
                    onDragEnd={onMarkerDragEnd}
                />
            </GoogleMap>
        </>
    );
}
