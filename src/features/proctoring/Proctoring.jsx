import React, { useEffect, useRef, useState } from "react";
import * as faceapi from "face-api.js";

export default function Proctoring() {

  const videoRef = useRef();
  const [warning, setWarning] = useState("");

  useEffect(() => {
    startCamera();
    loadModels();
    detectFace();

    detectTabSwitch();
    blockCopyPaste();
    detectSound();
  }, []);

  // Start Camera
  const startCamera = async () => {
    const stream = await navigator.mediaDevices.getUserMedia({
      video: true,
      audio: true
    });

    videoRef.current.srcObject = stream;
  };

  // Load AI Models
  const loadModels = async () => {
    await faceapi.nets.tinyFaceDetector.loadFromUri("/models");
  };

  // Face Detection
  const detectFace = () => {
    setInterval(async () => {

      const detections = await faceapi.detectAllFaces(
        videoRef.current,
        new faceapi.TinyFaceDetectorOptions()
      );

      if (detections.length === 0) {
        setWarning("No face detected!");
      }

      if (detections.length > 1) {
        setWarning("Multiple faces detected!");
      }

    }, 2000);

  };

  // Tab Switch Detection
  const detectTabSwitch = () => {

    document.addEventListener("visibilitychange", () => {

      if (document.hidden) {
        setWarning("Tab switched!");
      }

    });

  };

  // Block Copy Paste
  const blockCopyPaste = () => {

    document.addEventListener("copy", (e) => {
      e.preventDefault();
      setWarning("Copy not allowed!");
    });

    document.addEventListener("paste", (e) => {
      e.preventDefault();
      setWarning("Paste not allowed!");
    });

  };

  // Microphone Noise Detection
  const detectSound = async () => {

    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });

    const audioContext = new AudioContext();
    const mic = audioContext.createMediaStreamSource(stream);

    const analyser = audioContext.createAnalyser();

    mic.connect(analyser);

    const data = new Uint8Array(analyser.frequencyBinCount);

    setInterval(() => {

      analyser.getByteFrequencyData(data);

      const volume = data.reduce((a, b) => a + b) / data.length;

      if (volume > 60) {
        setWarning("Voice detected!");
      }

    }, 1000);

  };

  return (
    <div>

      <h2>AI Proctoring Monitor</h2>

      <video
        ref={videoRef}
        autoPlay
        muted
        width="400"
      />

      <h3 style={{color:"red"}}>{warning}</h3>

    </div>
  );
}