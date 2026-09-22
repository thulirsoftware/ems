import React, { useEffect, useRef, useState } from "react";
import * as faceapi from "face-api.js";

const VOICE_THRESHOLD = 60;

export default function Proctoring() {

  const videoRef = useRef();
  const streamRef = useRef(null);
  const [warnings, setWarnings] = useState({});

  useEffect(() => {

    let cancelled = false;
    let faceInterval = null;
    let soundInterval = null;
    let audioContext = null;

    const setWarning = (key, message) => {
      if (cancelled) return;

      setWarnings((prev) => {
        if (prev[key] === message) return prev;
        return { ...prev, [key]: message };
      });
    };

    const handleVisibilityChange = () => {
      setWarning("tabSwitch", document.hidden ? "Tab switched!" : null);
    };

    const handleCopy = (e) => {
      e.preventDefault();
      setWarning("copyPaste", "Copy not allowed!");
    };

    const handlePaste = (e) => {
      e.preventDefault();
      setWarning("copyPaste", "Paste not allowed!");
    };

    document.addEventListener("visibilitychange", handleVisibilityChange);
    document.addEventListener("copy", handleCopy);
    document.addEventListener("paste", handlePaste);

    const start = async () => {

      let stream;

      try {
        stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
      } catch {
        setWarning("camera", "Camera/microphone access is required for proctoring. Please allow access and reload the page.");
        return;
      }

      if (cancelled) {
        stream.getTracks().forEach((track) => track.stop());
        return;
      }

      streamRef.current = stream;
      videoRef.current.srcObject = stream;

      try {
        await faceapi.nets.tinyFaceDetector.loadFromUri("/models");
      } catch {
        setWarning("models", "Face detection could not be loaded.");
        return;
      }

      if (cancelled) return;

      // Face Detection
      faceInterval = setInterval(async () => {

        if (!videoRef.current) return;

        try {

          const detections = await faceapi.detectAllFaces(
            videoRef.current,
            new faceapi.TinyFaceDetectorOptions()
          );

          if (detections.length === 0) {
            setWarning("face", "No face detected!");
          } else if (detections.length > 1) {
            setWarning("face", "Multiple faces detected!");
          } else {
            setWarning("face", null);
          }

        } catch {
          // Video frame not ready yet — skip this tick.
        }

      }, 2000);

      // Microphone Noise Detection — reuses the camera stream's audio track
      // instead of requesting a second, independent microphone stream.
      try {

        audioContext = new AudioContext();

        const mic = audioContext.createMediaStreamSource(stream);
        const analyser = audioContext.createAnalyser();

        mic.connect(analyser);

        const data = new Uint8Array(analyser.frequencyBinCount);

        soundInterval = setInterval(() => {

          analyser.getByteFrequencyData(data);

          const volume = data.reduce((a, b) => a + b, 0) / data.length;

          setWarning("voice", volume > VOICE_THRESHOLD ? "Voice detected!" : null);

        }, 1000);

      } catch {
        // Audio monitoring unavailable — face/tab/copy-paste monitoring still works.
      }

    };

    start();

    return () => {

      cancelled = true;

      if (faceInterval) clearInterval(faceInterval);
      if (soundInterval) clearInterval(soundInterval);
      if (audioContext) audioContext.close();

      if (streamRef.current) {
        streamRef.current.getTracks().forEach((track) => track.stop());
        streamRef.current = null;
      }

      document.removeEventListener("visibilitychange", handleVisibilityChange);
      document.removeEventListener("copy", handleCopy);
      document.removeEventListener("paste", handlePaste);

    };

  }, []);

  const activeWarning = Object.values(warnings).find(Boolean) || "";

  return (
    <div>

      <h2>AI Proctoring Monitor</h2>

      <video
        ref={videoRef}
        autoPlay
        muted
        width="400"
      />

      <h3 style={{ color: "red" }}>{activeWarning}</h3>

    </div>
  );
}
