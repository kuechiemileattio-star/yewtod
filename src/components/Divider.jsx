import React from "react";
import { T } from "../theme.js";

export default function Divider({ margin = "3rem 0" }) {
  return <div style={{ height: 1, background: T.line, margin }} />;
}
