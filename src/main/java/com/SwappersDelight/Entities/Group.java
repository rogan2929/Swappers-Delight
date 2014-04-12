/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

package com.SwappersDelight.Entities;

/**
 *
 * @author rogan2929
 */
public class Group extends GraphObject {
    private String name;
    private ImageObject icon;

    public Group() {
    }

    public Group(String name, ImageObject icon) {
        this.name = name;
        this.icon = icon;
    }

    public String getName() {
        return name;
    }

    public void setName(String name) {
        this.name = name;
    }

    public ImageObject getIcon() {
        return icon;
    }

    public void setIcon(ImageObject icon) {
        this.icon = icon;
    }
}
